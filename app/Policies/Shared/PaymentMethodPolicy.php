<?php

namespace App\Policies\Shared;

use App\Models\Shared\PaymentMethod;
use App\Models\Users\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:payment-methods');
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('viewAny:payment-methods') || $user->can('view:payment-methods');
    }

    public function create(User $user): bool
    {
        return $user->can('create:payment-methods');
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('update:payment-methods');
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('delete:payment-methods');
    }

    public function restore(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('restore:payment-methods');
    }

    public function forceDelete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('forceDelete:payment-methods');
    }
}
