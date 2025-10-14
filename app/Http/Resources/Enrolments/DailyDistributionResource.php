<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyDistributionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'count' => $this['count'],
        ];
    }
}
