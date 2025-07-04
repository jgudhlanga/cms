<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $guard_name
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $deleted_at
 * @property mixed $first_name
 * @property mixed $middle_name
 * @property mixed $last_name
 * @property mixed $email
 * @property mixed $tenant_id
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                "avatarUrl" => $this?->avatarUrl,
                $this->mergeWhen($request->routeIs('users.*'), [
                    'createdAt' => $this->created_at,
                    'updatedAt' => $this->updated_at,
                    'deletedAt' => $this->deleted_at,
                ]),
            ]
        ];
    }
}
