<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Who may refund or cancel a payment.
     *
     * Tenancy is already enforced in the controller against session('shop_id'),
     * so this only answers "is this operator allowed to move money".
     */
    public function refund(User $user, Order $order): bool
    {
        // Belt and braces: never let an order from another shop through, even
        // if a future caller forgets the controller check.
        if ((int) $order->shop_id !== (int) session('shop_id')) {
            return false;
        }

        // ---- ADAPT THIS LINE to your roles setup. Some options: ----
        //
        //   Spatie permissions:  return $user->can('refund orders');
        //   A role column:       return in_array($user->role, ['owner', 'admin'], true);
        //   A boolean flag:      return (bool) $user->is_admin;
        //   Everyone signed in:  return true;
        //
        return true;
    }
}
