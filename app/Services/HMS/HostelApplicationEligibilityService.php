<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelEligibilityContextEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\Shared\Address;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;

class HostelApplicationEligibilityService
{
    /**
     * @return list<array{key: string, passed: bool, message: string, severity: string, modeOfStudy?: string|null}>
     */
    public function evaluate(
        Student $student,
        ?StudentEnrolment $enrolment = null,
        ?HmsSetting $settings = null,
        HostelEligibilityContextEnum $context = HostelEligibilityContextEnum::APPLICATION,
    ): array {
        $settings ??= HmsSetting::resolveForTenant($student->tenant_id);
        $enrolment ??= $student->latestEnrolment;

        $rules = [];

        if ($settings->require_full_time_study) {
            $modeOfStudy = trim((string) $enrolment?->modeOfStudy?->name);
            $modeName = strtolower($modeOfStudy);
            $expected = strtolower(trim($settings->full_time_mode_name));
            $passed = $modeName !== '' && $modeName === $expected;

            $rules[] = [
                'key' => 'full_time_study',
                'passed' => $passed,
                'severity' => $passed ? 'info' : 'warning',
                'modeOfStudy' => $modeOfStudy !== '' ? $modeOfStudy : null,
                'message' => $passed
                    ? __('hms.eligibility_full_time_passed', ['mode' => $modeOfStudy])
                    : __('hms.eligibility_full_time_failed', ['mode' => $modeOfStudy !== '' ? $modeOfStudy : __('hms.eligibility_mode_unknown')]),
            ];
        }

        if ($settings->require_tuition_paid) {
            $passed = (bool) $enrolment?->studentProgram?->hasPaid(FeeTypeEnum::TUITION_FEE);

            $rules[] = [
                'key' => 'tuition_paid',
                'passed' => $passed,
                'severity' => $passed ? 'success' : 'warning',
                'message' => $passed
                    ? __('hms.eligibility_tuition_paid_passed')
                    : __('hms.eligibility_tuition_paid_failed'),
            ];
        }

        if ($context === HostelEligibilityContextEnum::AWAITING_PAYMENT && $settings->require_accommodation_paid) {
            $passed = (bool) $enrolment?->studentProgram?->hasPaid(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);

            if (! $passed) {
                $application = HostelApplication::query()
                    ->where('student_id', $student->id)
                    ->whereIn('status', [
                        HostelApplicationStatusEnum::AWAITING_PAYMENT,
                        HostelApplicationStatusEnum::PARTIALLY_PAID,
                        HostelApplicationStatusEnum::PAID,
                    ])
                    ->latest()
                    ->first();

                if ($application instanceof HostelApplication) {
                    $passed = $application->hasPaidAccommodationFee();
                }
            }

            $rules[] = [
                'key' => 'accommodation_paid',
                'passed' => $passed,
                'severity' => $passed ? 'success' : 'warning',
                'message' => $passed
                    ? __('hms.eligibility_accommodation_paid_passed')
                    : __('hms.eligibility_accommodation_paid_failed'),
            ];
        }

        if ($settings->require_address_outside_campus) {
            $passed = $this->addressIsOutsideCampus($student, $settings->campus_city);

            $rules[] = [
                'key' => 'address_outside_campus',
                'passed' => $passed,
                'severity' => $passed ? 'info' : 'warning',
                'message' => $passed
                    ? __('hms.eligibility_address_passed', ['city' => $settings->campus_city])
                    : __('hms.eligibility_address_failed', ['city' => $settings->campus_city]),
            ];
        }

        return $rules;
    }

    /**
     * @param  list<array{key: string, passed: bool, message: string, severity?: string}>  $rules
     */
    public function allPassed(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (! ($rule['passed'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{key: string, passed: bool, message: string}>  $rules
     */
    public function addressOutsideCampusPassed(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (($rule['key'] ?? '') === 'address_outside_campus') {
                return (bool) ($rule['passed'] ?? false);
            }
        }

        return false;
    }

    private function addressIsOutsideCampus(Student $student, string $campusCity): bool
    {
        $address = $student->addresses()
            ->where('address_is_main', true)
            ->first()
            ?? $student->addresses()->first();

        if (! $address instanceof Address) {
            return false;
        }

        $cityFields = array_filter([
            $address->address_4,
            $address->address_5,
            is_array($address->meta) ? ($address->meta['city'] ?? null) : null,
            is_array($address->meta) ? ($address->meta['town'] ?? null) : null,
        ]);

        if ($cityFields === []) {
            return false;
        }

        $campus = strtolower(trim($campusCity));

        foreach ($cityFields as $field) {
            if (str_contains(strtolower(trim((string) $field)), $campus)) {
                return false;
            }
        }

        return true;
    }
}
