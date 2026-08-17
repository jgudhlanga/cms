<?php

declare(strict_types=1);

namespace App\Http\Resources\Students;

use App\Models\Students\StudentIdCardSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentIdCardSetting
 */
class StudentIdCardSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'institutionName' => $this->institution_name,
            'website' => $this->website,
            'returnName' => $this->return_name,
            'returnAddress' => $this->return_address,
            'returnPhone' => $this->return_phone,
            'logoUrl' => $this->logoUrl(),
            'signatureUrl' => $this->signatureUrl(),
        ];
    }
}
