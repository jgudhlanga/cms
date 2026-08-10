<?php

declare(strict_types=1);

namespace App\Http\Requests\Institution;

use App\Models\Institution\InstitutionFeature;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInstitutionFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage:institution-features') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            InstitutionFeature::ALLOW_ONLINE_CLEARANCE => ['required', 'boolean'],
        ];
    }
}
