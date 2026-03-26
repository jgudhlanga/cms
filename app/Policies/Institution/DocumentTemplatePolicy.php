<?php

namespace App\Policies\Institution;

use App\Models\Institution\DocumentTemplate;
use App\Models\Users\User;

class DocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:document-templates');
    }

    public function view(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('viewAny:document-templates') || $user->can('view:document-templates');
    }

    public function create(User $user): bool
    {
        return $user->can('create:document-templates');
    }

    public function update(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('update:document-templates', $documentTemplate);
    }

    public function delete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('delete:document-templates', $documentTemplate);
    }

    public function restore(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('restore:document-templates', $documentTemplate);
    }

    public function forceDelete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can('forceDelete:document-templates', $documentTemplate);
    }
}
