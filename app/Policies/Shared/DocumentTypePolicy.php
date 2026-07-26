<?php

namespace App\Policies\Shared;

use App\Models\Shared\DocumentType;
use App\Models\Users\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:document-types');
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $user->can('viewAny:document-types') || $user->can('view:document-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:document-types');
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->can('update:document-types');
    }

    public function delete(User $user, DocumentType $documentType): bool
    {
        return $user->can('delete:document-types');
    }

    public function restore(User $user, DocumentType $documentType): bool
    {
        return $user->can('restore:document-types');
    }

    public function forceDelete(User $user, DocumentType $documentType): bool
    {
        return $user->can('forceDelete:document-types');
    }
}
