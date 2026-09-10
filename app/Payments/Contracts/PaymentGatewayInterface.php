<?php

namespace App\Payments\Contracts;

use App\Payments\DTO\GatewayResponse;
use App\Payments\DTO\RefundRequest;

interface PaymentGatewayInterface
{
    /** Machine name, e.g. "viva_wallet". */
    public function provider(): string;

    /** Return money for a settled/captured payment. */
    public function refund(RefundRequest $request): GatewayResponse;

    /**
     * Release an authorisation that has not settled yet.
     * Some providers use one endpoint for both; they may delegate to refund().
     */
    public function cancel(RefundRequest $request): GatewayResponse;

    /** True when the provider can only reverse the full amount before settlement. */
    public function supportsPartialCancel(): bool;

    /**
     * Check the stored credentials authenticate.
     *
     * MUST be read-only. Every implementation issues a GET against a resource
     * that does not exist, so a 401/403 means bad credentials and anything else
     * (typically 404) means we got through. Nothing here can move money — these
     * run against live accounts.
     */
    public function verifyCredentials(): GatewayResponse;
}
