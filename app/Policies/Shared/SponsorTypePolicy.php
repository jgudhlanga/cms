<?php

namespace App\Policies\Shared;

use App\Models\Shared\SponsorType;
use App\Models\Users\User;

class SponsorTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:sponsor-types');
    }

    public function view(User $user, SponsorType $sponsorType): bool
    {
        return $user->can('viewAny:sponsor-types') || $user->can('view:sponsor-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:sponsor-types');
    }

    public function update(User $user, SponsorType $sponsorType): bool
    {
        return $user->can('update:sponsor-types');
    }

    public function delete(User $user, SponsorType $sponsorType): bool
    {
        return $user->can('delete:sponsor-types');
    }

    public function restore(User $user, SponsorType $sponsorType): bool
    {
        return $user->can('restore:sponsor-types');
    }

    public function forceDelete(User $user, SponsorType $sponsorType): bool
    {
        return $user->can('forceDelete:sponsor-types');
    }
}
