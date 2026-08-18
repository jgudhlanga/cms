<?php

declare(strict_types=1);

namespace App\Support\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardSetting;
use Carbon\CarbonInterface;

final readonly class StudentIdCardFace
{
    /**
     * @var list<string>
     */
    public const RELATIONS = [
        'user',
        'latestEnrolment.departmentCourse.course',
        'latestEnrolment.institutionDepartment.department',
        'latestEnrolment.departmentLevel.level',
        'latestEnrolment.modeOfStudy',
        'activeHostelAllocation',
    ];

    public function __construct(
        public string $studentName,
        public string $studentNumber,
        public string $department,
        public string $level,
        public string $course,
        public string $mode,
        public string $sdp,
        public string $residence,
        public string $expiryDate,
        public string $nationalId,
        public string $identityLabel,
        public string $returnName,
        public string $returnAddress,
        public string $returnPhone,
        public string $institutionName,
        public string $website,
        public ?string $logoUrl,
        public ?string $signatureUrl,
    ) {}

    public static function fromStudent(
        ?Student $student,
        ?CarbonInterface $now = null,
        ?StudentIdCardSetting $settings = null,
    ): self {
        $now ??= now();
        $enrolment = $student?->latestEnrolment;
        $levelName = (string) ($enrolment?->departmentLevel?->level?->name ?? '');
        $settings ??= StudentIdCardSetting::resolveForTenant($student?->tenant_id !== null ? (int) $student->tenant_id : null);
        $defaults = StudentIdCardSetting::defaultAttributes();

        return new self(
            studentName: $student?->user?->full_name ?? '',
            studentNumber: (string) ($student?->student_number ?? ''),
            department: (string) ($enrolment?->institutionDepartment?->department?->name ?? ''),
            level: $levelName,
            course: (string) ($enrolment?->departmentCourse?->course?->name ?? ''),
            mode: self::modeLabel($enrolment?->modeOfStudy?->name),
            sdp: self::isSdp($levelName) ? 'Yes' : 'No',
            residence: $student?->activeHostelAllocation !== null ? 'RES' : 'NON Res',
            expiryDate: $now->copy()->endOfYear()->format('d M Y'),
            nationalId: self::identityNumber($student),
            identityLabel: self::identityLabelFor($student),
            returnName: (string) ($settings->return_name ?: $defaults['return_name']),
            returnAddress: (string) ($settings->return_address ?: $defaults['return_address']),
            returnPhone: (string) ($settings->return_phone ?: $defaults['return_phone']),
            institutionName: (string) ($settings->institution_name ?: $defaults['institution_name']),
            website: (string) ($settings->website ?: $defaults['website']),
            logoUrl: $settings->exists ? $settings->logoUrl() : '/'.StudentIdCardSetting::FALLBACK_LOGO_PATH,
            signatureUrl: $settings->exists ? $settings->signatureUrl() : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'studentName' => $this->studentName,
            'studentNumber' => $this->studentNumber,
            'department' => $this->department,
            'level' => $this->level,
            'course' => $this->course,
            'mode' => $this->mode,
            'sdp' => $this->sdp,
            'residence' => $this->residence,
            'expiryDate' => $this->expiryDate,
            'nationalId' => $this->nationalId,
            'identityLabel' => $this->identityLabel,
            'returnName' => $this->returnName,
            'returnAddress' => $this->returnAddress,
            'returnPhone' => $this->returnPhone,
            'institutionName' => $this->institutionName,
            'website' => $this->website,
            'logoUrl' => $this->logoUrl,
            'signatureUrl' => $this->signatureUrl,
        ];
    }

    /**
     * @return list<string>
     */
    public static function requestRelations(): array
    {
        return array_map(
            fn (string $relation): string => 'student.'.$relation,
            self::RELATIONS,
        );
    }

    private static function isSdp(string $levelName): bool
    {
        return strcasecmp($levelName, LevelEnum::SDP->value) === 0;
    }

    private static function identityNumber(?Student $student): string
    {
        if ($student === null) {
            return '';
        }

        if ($student->isZimbabwean()) {
            return trim((string) $student->id_number);
        }

        return trim((string) $student->passport_number);
    }

    private static function identityLabelFor(?Student $student): string
    {
        if ($student !== null && ! $student->isZimbabwean()) {
            return __('trans.student_id_card_passport_number');
        }

        return __('trans.student_id_card_national_id');
    }

    private static function modeLabel(?string $name): string
    {
        if ($name === null || $name === '') {
            return '';
        }

        $mode = ModeOfStudyEnum::tryFrom($name) ?? ModeOfStudyEnum::tryFromLabel($name);

        return match ($mode) {
            ModeOfStudyEnum::FULL_TIME => 'Full time',
            ModeOfStudyEnum::PART_TIME => 'Part time',
            ModeOfStudyEnum::BLOCK_RELEASE => 'Block',
            ModeOfStudyEnum::OJET => 'Ojet',
            null => $name,
        };
    }
}
