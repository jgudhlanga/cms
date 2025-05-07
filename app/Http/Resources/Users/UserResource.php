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
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
				'email' => $this->email,
				'avatar' => $this->avatar,
				'tenantId' => $this->tenant_id,
                $this->mergeWhen($request->routeIs('users.*'), [
                    'createdAt' => $this->created_at,
                    'updatedAt' => $this->updated_at,
                    'deletedAt' => $this->deleted_at,
                ]),
            ]
        ];
    }
}
