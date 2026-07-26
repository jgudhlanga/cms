<?php

namespace App\Http\Resources\AuditTrail;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditTrailResource extends JsonResource
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
    ];

    public function toArray(Request $request): array
    {
        $properties = $this->properties['attributes'] ?? [];

        return [
            'type' => 'audit-trail',
            'id' => $this->resource->id,
            'attributes' => [
                'logName' => $this->resource->log_name,
                'description' => $this->resource->description,
                'subjectType' => $this->resource->subject_type,
                'subjectId' => $this->resource->subject_id,
                'causerType' => $this->resource->causer_type,
                'causer' => $this->getCauserName(),
                'properties' => $this->sanitizeProperties(is_array($properties) ? $properties : []),
                'batchUuid' => $this->resource->batch_uuid,
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
            ],
        ];
    }

    private function getCauserName(): string
    {
        return User::find($this->resource->causer_id)->name
            ?? User::find(User::SUPER_ADMINISTRATOR)->name
            ?? '';
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function sanitizeProperties(array $properties): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            unset($properties[$key]);
        }

        return $properties;
    }
}
