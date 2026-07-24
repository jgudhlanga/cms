<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSummaryResource extends JsonResource
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
                'avatarUrl' => $this?->avatarUrl,
                'statusId' => $this->status_id,
                'status' => $this->status?->title,
            ],
        ];
    }
}
