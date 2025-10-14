<?php

namespace App\Http\Resources\Enrolments;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyDistributionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'date' => Carbon::parse($this['date'])->format('d M'),
            'count' => $this['count'],
        ];
    }
}
