<?php

namespace App\Policies\Shared;

use App\Models\Shared\Language;
use App\Models\Users\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:languages');
    }

    public function view(User $user, Language $language): bool
    {
        return $user->can('viewAny:languages') || $user->can('view:languages');
    }

    public function create(User $user): bool
    {
        return $user->can('create:languages');
    }

    public function update(User $user, Language $language): bool
    {
        return $user->can('update:languages');
    }

    public function delete(User $user, Language $language): bool
    {
        return $user->can('delete:languages');
    }

    public function restore(User $user, Language $language): bool
    {
        return $user->can('restore:languages');
    }

    public function forceDelete(User $user, Language $language): bool
    {
        return $user->can('forceDelete:languages');
    }
}
