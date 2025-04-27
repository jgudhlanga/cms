<?php

namespace App\Http\Resources\AuditTrail;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditTrailResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'audit-trail',
			'id' => $this->resource->id,
			"attributes" => [
				'logName' => $this->resource->log_name,
				'description' => $this->resource->description,
				'subjectType' => $this->resource->subject_type,
				'subjectId' => $this->resource->subject_id,
				'causerType' => $this->resource->causer_type,
				'causer' => $this->getCauserName(),
				'properties' => $this->properties['attributes'] ?? [],
				'batchUuid' => $this->resource->batch_uuid,
				'createdAt' => $this->resource->created_at,
				'updatedAt' => $this->resource->updated_at,
			]
		];
	}

	private function getCauserName(): string
	{
		return User::find($this->resource->causer_id)->name
			?? User::find(User::SUPER_USER)->name
			?? '';
	}
}

