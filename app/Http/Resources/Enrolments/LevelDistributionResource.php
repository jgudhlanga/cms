<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelDistributionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'levelId' => $this->level_id,
            'levelName' => $this->level_name,
            'levelCount' => $this->level_count,
        ];
    }
}
