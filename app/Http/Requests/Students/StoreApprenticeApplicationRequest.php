<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\ApplicationTrackEnum;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApprenticeApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employer' => ['required', 'string', 'max:255'],
            'apprentice_number' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $track = app(ApplicationTrackSession::class)->get();

            if ($track !== ApplicationTrackEnum::Apprentice) {
                $validator->errors()->add('track', __('trans.application_track_not_open'));
            }

            if (! app(RegistrationAvailabilityService::class)->isApprenticeRegistrationOpen()) {
                $validator->errors()->add('track', __('trans.application_track_not_open'));
            }
        });
    }
}
