<?php

namespace App\Policies\Shared;

use App\Models\Shared\PaymentFrequency;
use App\Models\Users\User;

class PaymentFrequencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:payment-frequencies');
    }

    public function view(User $user, PaymentFrequency $paymentFrequency): bool
    {
        return $user->can('viewAny:payment-frequencies') || $user->can('view:payment-frequencies');
    }

    public function create(User $user): bool
    {
        return $user->can('create:payment-frequencies');
    }

    public function update(User $user, PaymentFrequency $paymentFrequency): bool
    {
        return $user->can('update:payment-frequencies');
    }

    public function delete(User $user, PaymentFrequency $paymentFrequency): bool
    {
        return $user->can('delete:payment-frequencies');
    }

    public function restore(User $user, PaymentFrequency $paymentFrequency): bool
    {
        return $user->can('restore:payment-frequencies');
    }

    public function forceDelete(User $user, PaymentFrequency $paymentFrequency): bool
    {
        return $user->can('forceDelete:payment-frequencies');
    }
}
