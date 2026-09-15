<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff directory entry for unauthenticated consumers (public website). Same envelope as
 * StaffResource, without identity documents, date of birth, demographics or contact details.
 */
class PublicStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'title',
            'employmentType',
            'user.roles',
            'institutionDepartments.department',
        ]);

        $user = $this->user;

        return [
            'type' => 'staff',
            'id' => $this->id,
            'attributes' => [
                'userId' => $this->user_id,
                'title' => $this->title?->name,
                'employmentType' => $this->employmentType?->name,
                'departments' => $this->institutionDepartments
                    ->map(fn ($institutionDepartment): array => [
                        'id' => $institutionDepartment->id,
                        'name' => $institutionDepartment->department?->name,
                    ])
                    ->values(),
            ],
            'relationships' => [
                'user' => $user === null ? null : [
                    'type' => 'user',
                    'id' => $user->id,
                    'attributes' => [
                        'name' => $user->full_name,
                        'firstname' => $user->first_name,
                        'middleName' => $user->middle_name,
                        'lastname' => $user->last_name,
                    ],
                ],
                'roles' => ($user?->roles ?? collect())
                    ->map(fn ($role): array => [
                        'type' => 'role',
                        'id' => $role->id,
                        'attributes' => [
                            'name' => $role->name,
                            'slug' => $role->slug,
                        ],
                    ])
                    ->values(),
            ],
        ];
    }
}
