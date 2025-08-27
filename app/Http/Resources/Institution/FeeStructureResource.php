<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeStructureResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'fee-structure',
            'id' => $this->id,
            'attributes' => [
                'feeTypeId' => $this->fee_type_id,
                'levelId' => $this->level_id,
                'modeOfStudyId' => $this->mode_of_study_id,
                'amount' => $this->amount,
                'localFcaAmount' => $this->local_fca_amount,
                $this->mergeWhen($request->routeIs('fee-structures.*'), [
                    'createdAt' => $this->created_at,
                    'updatedAt' => $this->updated_at,
                    'deletedAt' => $this->deleted_at,
                ]),
            ],
        ];
    }
}
