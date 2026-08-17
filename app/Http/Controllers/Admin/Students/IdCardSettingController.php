<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentIdCardSettingRequest;
use App\Models\Students\StudentIdCardSetting;
use App\Services\Students\StudentIdCardSettingService;
use Illuminate\Http\RedirectResponse;

class IdCardSettingController extends Controller
{
    public function __construct(
        private readonly StudentIdCardSettingService $idCardSettingService,
    ) {}

    public function update(UpdateStudentIdCardSettingRequest $request): RedirectResponse
    {
        $settings = StudentIdCardSetting::resolveForTenant();
        $this->authorize('update', $settings);

        $this->idCardSettingService->update(
            $settings,
            $request->safe()->except(['logo', 'principal_signature']),
            $request->file('logo'),
            $request->file('principal_signature'),
        );

        return back()->with('success', __('students.id_card_settings_saved'));
    }
}
