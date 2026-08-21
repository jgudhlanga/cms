<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use App\Enums\Students\ApplicationTrackEnum;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\RegistrationIntentSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferCollegeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'college_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $track = $this->user() !== null
                ? app(ApplicationTrackSession::class)->get()
                : app(RegistrationIntentSession::class)->getTrack();

            if ($track !== ApplicationTrackEnum::Transfer) {
                $validator->errors()->add('track', __('trans.application_track_not_open'));

                return;
            }

            if (! app(RegistrationAvailabilityService::class)->isTransferRegistrationOpen()) {
                $validator->errors()->add('track', __('trans.application_track_not_open'));
            }
        });
    }
}
