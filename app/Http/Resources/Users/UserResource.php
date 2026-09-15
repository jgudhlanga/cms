<?php

namespace App\Http\Resources\Users;

use App\Helpers\Helper;
use App\Http\Resources\Preferences\UserPreferenceResource;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    private bool $forSharedProps = false;

    /**
     * Shared Inertia props need the signed-in user's identity and a few flags on every navigation,
     * so this variant skips the profile, contact and address lookups of the full resource.
     */
    public static function forSharedProps(User $user): self
    {
        $resource = new self($user);
        $resource->forSharedProps = true;

        return $resource;
    }

    public function toArray(Request $request): array
    {
        if ($this->forSharedProps) {
            return $this->sharedPropsArray();
        }

        $this->resource->loadMissing([
            'tenant',
            'status',
            'roles',
            'studentProfile.contacts',
            'studentProfile.addresses',
            'staffProfile.contacts',
            'staffProfile.addresses',
        ]);

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
                'mainContact' => ContactResource::make($this->resolveMainContact()),
                'mainAddress' => AddressResource::make($this->resolveMainAddress()),
                'preference' => $this->whenLoaded(
                    'preference',
                    fn () => UserPreferenceResource::make($this->preference),
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedPropsArray(): array
    {
        $this->resource->loadMissing(['studentProfile', 'staffProfile']);

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
                'statusId' => $this->status_id,
                'avatarUrl' => $this->avatar_url,
                'hasStudentProfile' => $this->has_student_profile,
                'studentId' => $this->studentProfile?->id,
                'hasProgram' => $this->studentProfile?->has_program,
                'hasStaffProfile' => $this->has_staff_profile,
                'staffId' => $this->staffProfile?->id,
                'canImpersonate' => $this->can_impersonate,
                'hasAccessToNonAcademicDepartments' => Helper::hasAccessToNonAcademicDepartments(),
            ],
        ];
    }

    private function resolveMainContact(): mixed
    {
        if ($this->has_staff_profile && $this->relationLoaded('staffProfile')) {
            return $this->staffProfile?->contacts?->firstWhere('contact_is_main', 1);
        }

        if ($this->has_student_profile && $this->relationLoaded('studentProfile')) {
            return $this->studentProfile?->contacts?->firstWhere('contact_is_main', 1);
        }

        return null;
    }

    private function resolveMainAddress(): mixed
    {
        if ($this->has_staff_profile && $this->relationLoaded('staffProfile')) {
            return $this->staffProfile?->addresses?->firstWhere('address_is_main', 1);
        }

        if ($this->has_student_profile && $this->relationLoaded('studentProfile')) {
            return $this->studentProfile?->addresses?->firstWhere('address_is_main', 1);
        }

        return null;
    }
}
