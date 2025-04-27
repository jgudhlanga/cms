<?php

namespace App\Http\Resources\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentDayResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'payment-day',
			'id' => $this->resource->id,
			"attributes" => [
				'title' => $this->resource->title,
				$this->mergeWhen($request->routeIs('payment-days.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			]
		];
	}
}
