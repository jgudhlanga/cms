<?php

namespace App\Policies\Shared;

use App\Models\Shared\PaymentDay;
use App\Models\Users\User;

class PaymentDayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:payment-days');
    }

    public function view(User $user, PaymentDay $paymentDay): bool
    {
        return $user->can('viewAny:payment-days') || $user->can('view:payment-days');
    }

    public function create(User $user): bool
    {
        return $user->can('create:payment-days');
    }

    public function update(User $user, PaymentDay $paymentDay): bool
    {
        return $user->can('update:payment-days');
    }

    public function delete(User $user, PaymentDay $paymentDay): bool
    {
        return $user->can('delete:payment-days');
    }

    public function restore(User $user, PaymentDay $paymentDay): bool
    {
        return $user->can('restore:payment-days');
    }

    public function forceDelete(User $user, PaymentDay $paymentDay): bool
    {
        return $user->can('forceDelete:payment-days');
    }
}
