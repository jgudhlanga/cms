<?php

namespace App\Policies\Institution;

use App\Enums\Acl\PermissionEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Users\User;

class DocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_DOCUMENT_TEMPLATES);
    }

    public function view(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_DOCUMENT_TEMPLATES) || $user->can(PermissionEnum::VIEW_DOCUMENT_TEMPLATES);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_DOCUMENT_TEMPLATES);
    }

    public function update(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionEnum::UPDATE_DOCUMENT_TEMPLATES, $documentTemplate);
    }

    public function delete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionEnum::DELETE_DOCUMENT_TEMPLATES, $documentTemplate);
    }

    public function restore(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionEnum::RESTORE_DOCUMENT_TEMPLATES, $documentTemplate);
    }

    public function forceDelete(User $user, DocumentTemplate $documentTemplate): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_DOCUMENT_TEMPLATES, $documentTemplate);
    }
}
