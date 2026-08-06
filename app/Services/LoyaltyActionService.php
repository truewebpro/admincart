<?php

namespace App\Services;

use App\Models\CustomerShop;
use App\Models\LoyaltyActionCompletion;
use App\Models\LoyaltyEarnAction;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyActionService
{
    /**
     * List a shop's active earn actions with each one's status for this
     * customer AT THIS SHOP: 'available', 'pending' (awaiting admin review),
     * or 'completed' (already claimed, can't be claimed again under its
     * repeat_scope).
     */
    public function availableActions(CustomerShop $customerShop)
    {
        $actions = LoyaltyEarnAction::where('shop_id', $customerShop->shop_id)->active()->get();

        $completions = LoyaltyActionCompletion::where('cshop_id', $customerShop->cshop_id)
            ->whereIn('loyalty_earn_action_id', $actions->pluck('id'))
            ->get()
            ->groupBy('loyalty_earn_action_id');

        return $actions->map(function ($action) use ($completions) {
            $mine = $completions->get($action->id, collect());
            $status = 'available';

            if ($action->repeat_scope !== 'unlimited') {
                if ($mine->contains('status', 'pending')) {
                    $status = 'pending';
                } elseif ($action->repeat_scope === 'once_per_customer' && $mine->contains('status', 'approved')) {
                    $status = 'completed';
                }
                // 'once_per_reference' completion status is per-item, checked via canClaim().
            }

            return [
                'id' => $action->id,
                'category' => $action->category,
                'platform' => $action->platform,
                'label' => $action->label,
                'description' => $action->description,
                'action_url' => $action->action_url,
                'points' => $action->points,
                'verification' => $action->verification,
                'repeat_scope' => $action->repeat_scope,
                'status' => $status,
            ];
        });
    }

    /**
     * Whether this customer_shop can still claim this action for a given
     * reference (or at all, for non-referenced actions).
     */
    public function canClaim(CustomerShop $customerShop, LoyaltyEarnAction $action, ?string $referenceType = null, ?int $referenceId = null): bool
    {
        if (! $action->is_active) {
            return false;
        }

        if ($action->repeat_scope === 'unlimited') {
            return true;
        }

        $query = LoyaltyActionCompletion::where('cshop_id', $customerShop->cshop_id)
            ->where('loyalty_earn_action_id', $action->id)
            ->whereIn('status', ['pending', 'approved']);

        if ($action->repeat_scope === 'once_per_reference') {
            $query->where('reference_type', $referenceType)->where('reference_id', $referenceId);
        }

        return ! $query->exists();
    }

    /**
     * Customer claims an action (clicks "I followed", submits a review/share
     * link, etc), scoped to their CustomerShop for this shop. Automatic actions
     * are awarded immediately. Manual ones drop into the admin review queue as
     * 'pending' with no points yet.
     */
    public function claim(
        CustomerShop $customerShop,
        LoyaltyEarnAction $action,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $proofUrl = null,
    ): LoyaltyActionCompletion {
        if ($action->shop_id !== $customerShop->shop_id) {
            throw new RuntimeException('This reward is not available at this shop.');
        }

        if (! $this->canClaim($customerShop, $action, $referenceType, $referenceId)) {
            throw new RuntimeException('This reward has already been claimed or is no longer available.');
        }

        if ($action->verification === 'manual_admin' && ! $proofUrl && $action->category !== 'social_follow') {
            throw new RuntimeException('Please provide a link so we can verify this before awarding points.');
        }

        return DB::transaction(function () use ($customerShop, $action, $referenceType, $referenceId, $proofUrl) {
            $completion = LoyaltyActionCompletion::create([
                'shop_id' => $customerShop->shop_id,
                'cshop_id' => $customerShop->cshop_id,
                'customer_id' => $customerShop->customer_id,
                'loyalty_earn_action_id' => $action->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'proof_url' => $proofUrl,
                'status' => $action->verification === 'automatic' ? 'approved' : 'pending',
            ]);

            if ($action->verification === 'automatic') {
                $this->awardPoints($completion, $action, $customerShop);
            }

            return $completion->fresh();
        });
    }

    /**
     * Admin approves a pending manual-review completion (e.g. checked the
     * customer's Google review is real) and awards the points.
     */
    public function approve(LoyaltyActionCompletion $completion, int $adminId, ?string $notes = null): LoyaltyActionCompletion
    {
        if ($completion->status !== 'pending') {
            throw new RuntimeException('This claim has already been reviewed.');
        }

        return DB::transaction(function () use ($completion, $adminId, $notes) {
            $completion->update([
                'status' => 'approved',
                'admin_notes' => $notes,
                'reviewed_by_admin_id' => $adminId,
                'reviewed_at' => now(),
            ]);

            $this->awardPoints($completion, $completion->action, $completion->customerShop);

            return $completion->fresh();
        });
    }

    /**
     * Admin rejects a pending claim (fake review link, duplicate, etc). No points awarded.
     */
    public function reject(LoyaltyActionCompletion $completion, int $adminId, string $notes): LoyaltyActionCompletion
    {
        if ($completion->status !== 'pending') {
            throw new RuntimeException('This claim has already been reviewed.');
        }

        $completion->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);

        return $completion->fresh();
    }

    protected function awardPoints(LoyaltyActionCompletion $completion, LoyaltyEarnAction $action, CustomerShop $customerShop): void
    {
        $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

        $newBalance = $locked->loyalty_points_balance + $action->points;
        $locked->update(['loyalty_points_balance' => $newBalance]);

        $transaction = LoyaltyTransaction::create([
            'shop_id' => $locked->shop_id,
            'cshop_id' => $locked->cshop_id,
            'customer_id' => $locked->customer_id,
            'type' => 'earn',
            'points' => $action->points,
            'balance_after' => $newBalance,
            'notes' => "Earned from: {$action->label}",
            'created_by_type' => $action->verification === 'automatic' ? 'system' : 'admin',
        ]);

        $completion->update([
            'points_awarded' => $action->points,
            'loyalty_transaction_id' => $transaction->id,
        ]);
    }
}
