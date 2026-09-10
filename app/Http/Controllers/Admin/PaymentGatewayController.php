<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopPaymentGateway;
use App\Payments\Exceptions\GatewayException;
use App\Payments\GatewaySchema;
use App\Payments\PaymentGatewayManager;
use App\Support\ResolvesCurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    use ResolvesCurrentShop;

    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    /** The Vue form renders straight off this. */
    public function schema(): JsonResponse
    {
        return response()->json(['providers' => GatewaySchema::all()]);
    }

    public function index(): JsonResponse
    {
        $saved = ShopPaymentGateway::forShop($this->currentShopId())->get()->keyBy('provider');

        $rows = collect(GatewaySchema::all())->map(function (array $definition, string $provider) use ($saved) {
            $config  = $saved->get($provider);
            $secrets = GatewaySchema::secretKeys($provider);

            // Secrets go out as a "set / not set" mask only.
            $values = collect($config?->credentials ?? [])
                ->map(fn ($value, $key) => in_array($key, $secrets, true)
                    ? (filled($value) ? '••••••••' : null)
                    : $value)
                ->all();

            return [
                'provider'    => $provider,
                'label'       => $definition['label'],
                'configured'  => (bool) $config,
                'is_active'   => (bool) $config?->is_active,
                'is_default'  => (bool) $config?->is_default,
                'environment' => $config?->environment ?? 'sandbox',
                'credentials' => $values,
                'secret_keys' => $secrets,
            ];
        })->values();

        return response()->json(['gateways' => $rows]);
    }

    public function store(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, GatewaySchema::providers(), true), 404);

        $shopId = $this->currentShopId();

        $validated = $request->validate(array_merge([
            'environment' => ['required', 'in:sandbox,production'],
            'is_active'   => ['boolean'],
            'is_default'  => ['boolean'],
            'label'       => ['nullable', 'string', 'max:80'],
            'credentials' => ['required', 'array'],
        ], GatewaySchema::validationRules($provider, $request->all())));

        $existing    = ShopPaymentGateway::forShop($shopId)->where('provider', $provider)->first();
        $credentials = GatewaySchema::filter($provider, $validated['credentials']);

        // Blank or masked secret means "leave the stored one alone".
        foreach (GatewaySchema::secretKeys($provider) as $key) {
            $incoming = $credentials[$key] ?? null;

            if (blank($incoming) || $incoming === '••••••••') {
                $kept = data_get($existing?->credentials, $key);

                if ($kept === null) {
                    unset($credentials[$key]);
                } else {
                    $credentials[$key] = $kept;
                }
            }
        }

        $gateway = ShopPaymentGateway::updateOrCreate(
            ['shop_id' => $shopId, 'provider' => $provider],
            [
                'label'       => $validated['label'] ?? GatewaySchema::for($provider)['label'],
                'credentials' => $credentials,
                'environment' => $validated['environment'],
                'is_active'   => $validated['is_active'] ?? true,
                'is_default'  => $validated['is_default'] ?? false,
            ]
        );

        if ($gateway->is_default) {
            ShopPaymentGateway::forShop($shopId)
                ->where('id', '!=', $gateway->id)
                ->update(['is_default' => false]);
        }

        return response()->json(['message' => 'Saved.'], $existing ? 200 : 201);
    }

    /**
     * Read-only credential check. The driver issues a GET against a resource
     * that cannot exist, so this is safe to run against live accounts — it
     * proves the keys authenticate, not that a refund would be accepted.
     */
    public function test(string $provider): JsonResponse
    {
        abort_unless(in_array($provider, GatewaySchema::providers(), true), 404);

        try {
            $result = $this->gateways
                ->driver($this->currentShopId(), $provider)
                ->verifyCredentials();
        } catch (GatewayException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok'      => false,
                'message' => 'Could not reach ' . GatewaySchema::for($provider)['label']
                    . '. This is a network problem, not necessarily a credentials problem.',
            ], 422);
        }

        return response()->json([
            'ok'      => $result->successful,
            'message' => $result->successful
                ? 'Credentials accepted. This only checks authentication — it does not test a refund.'
                : $result->errorMessage,
            'detail'  => $result->raw,
        ], $result->successful ? 200 : 422);
    }

    public function destroy(string $provider): JsonResponse
    {
        ShopPaymentGateway::forShop($this->currentShopId())
            ->where('provider', $provider)
            ->delete();

        return response()->json(['message' => 'Removed.']);
    }
}
