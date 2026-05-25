<?php

namespace App\Http\Resources\Users;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasAccessToNonAcademicDepartments = Helper::hasAccessToNonAcademicDepartments();
        return [
            'type' => 'user',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->full_name,
                'firstname' => $this->first_name,
                'middleName' => $this->middle_name,
                'lastname' => $this->last_name,
                'email' => $this->email,
                'phoneNumber' => $this->phone_number,
                'tenantId' => $this->tenant_id,
                'tenant' => $this->tenant?->name,
                'statusId' => $this->status_id,
                'status' => $this->status?->title,
                'lastLoginAt' => $this->last_login_at,
                'loginCount' => $this->login_count ?? 0,
                'avatarUrl' => $this?->avatarUrl,
                'hasStudentProfile' => $this->has_student_profile,
                'studentId' => $this->studentProfile?->id,
                'hasProgram' => $this->studentProfile?->has_program,
                'idNumber' => $this->studentProfile?->id_number,
                'hasStaffProfile' => $this->has_staff_profile,
                'staffId' => $this->staffProfile?->id,
                'canImpersonate' => $this->can_impersonate,
                'canBeImpersonated' => $this->can_be_impersonated,
                'hasAccessToNonAcademicDepartments' => $hasAccessToNonAcademicDepartments,
                $this->mergeWhen($request->routeIs('users.*'), [
                    'createdAt' => $this->created_at,
                    'updatedAt' => $this->updated_at,
                    'deletedAt' => $this->deleted_at,
                ]),
            ],
            'relationships' => [
                'profile' => UserProfileData::forUser($this->resource),
                'roles' => collect($this->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])),
            ],
        ];
    }
}
