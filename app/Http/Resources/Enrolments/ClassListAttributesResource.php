<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassListAttributesResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'identityConfirmed' => $this['identity_confirmed'] ?? false,
            'disabilityConfirmed' => $this['disability_confirmed'] ?? false,
            'namesConfirmed' => $this['names_confirmed'] ?? false,
            'oLevelConfirmed' => $this['o_level_confirmed'] ?? false,
            'previousLevelConfirmed' => $this['previous_level_confirmed'] ?? false,
            'applicationFeeConfirmed' => $this['application_fee_confirmed'] ?? false,
            'proofOfPaymentConfirmed' => $this['proof_of_payment_confirmed'] ?? false,
            'passportPhotosConfirmed' => $this['passport_photos_confirmed'] ?? false,
            'originalBirthCertificateConfirmed' => $this['original_birth_certificate_confirmed'] ?? false,
            'originalNationalIdentityConfirmed' => $this['original_national_identity_confirmed'] ?? false,
            'originalEducationCertificatesConfirmed' => $this['original_education_certificates_confirmed'] ?? false,
        ];
    }
}
