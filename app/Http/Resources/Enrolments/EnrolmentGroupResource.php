<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        $pagination = $this['pagination'] ?? null;
        $groups = $this['groups'] ?? [];

        return [
            'pagination' => [
                'currentPage' => $pagination['current_page'] ?? null,
                'lastPage' => $pagination['last_page'] ?? null,
                'perPage' => $pagination['per_page'] ?? null,
                'total' => $pagination['total'] ?? null,
                'links' => $pagination['links'] ?? [],
            ],

            'groups' => [
                'disabled' => EnrolmentApplicationResource::collection($groups['disabled'] ?? collect()),
                'female'   => EnrolmentApplicationResource::collection($groups['female'] ?? collect()),
                'male'     => EnrolmentApplicationResource::collection($groups['male'] ?? collect()),
                'others'   => EnrolmentApplicationResource::collection($groups['others'] ?? collect()),
            ],
        ];
    }
}
