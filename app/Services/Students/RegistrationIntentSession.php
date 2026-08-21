<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\ApplicationTrackEnum;
use Illuminate\Support\Facades\Session;

class RegistrationIntentSession
{
    public const TRACK_KEY = 'registration.intent.track';

    public const LEVEL_KEY = 'registration.intent.level_id';

    public const INTAKE_KEY = 'registration.intent.intake_period_id';

    public const CONTINUOUS_FOCUS_KEY = 'registration.intent.continuous_focus';

    public const DEPARTMENT_LEVEL_KEY = 'registration.intent.department_level_id';

    public const COURSE_KEY = 'registration.intent.course_id';

    public const MODE_KEY = 'registration.intent.mode_of_study_id';

    public const DEPARTMENT_KEY = 'registration.intent.department_id';

    public const INSTRUCTIONS_KEY = 'registration.intent.instructions_acknowledged';

    public const READY_FOR_ACCOUNT_KEY = 'registration.intent.ready_for_account';

    public const REQUIRES_FEE_KEY = 'registration.intent.requires_fee';

    public const TRANSFER_COLLEGE_NAME_KEY = 'registration.intent.transfer_college_name';

    public const OJET_IDENTITY_TYPE_KEY = 'registration.intent.ojet_identity_type';

    public const OJET_ID_NUMBER_KEY = 'registration.intent.ojet_id_number';

    public const OJET_PASSPORT_NUMBER_KEY = 'registration.intent.ojet_passport_number';

    public const OJET_STUDENT_NUMBER_KEY = 'registration.intent.ojet_student_number';

    public const OJET_STUDENT_ID_KEY = 'registration.intent.ojet_student_id';

    public function getTrack(): ?ApplicationTrackEnum
    {
        $value = Session::get(self::TRACK_KEY);

        if (! is_string($value)) {
            return null;
        }

        return ApplicationTrackEnum::tryFrom($value);
    }

    public function setTrack(ApplicationTrackEnum $track): void
    {
        Session::put(self::TRACK_KEY, $track->value);
    }

    public function setContinuousFocus(?string $focus): void
    {
        if ($focus === null) {
            Session::forget(self::CONTINUOUS_FOCUS_KEY);

            return;
        }

        Session::put(self::CONTINUOUS_FOCUS_KEY, $focus);
    }

    public function continuousFocus(): ?string
    {
        $value = Session::get(self::CONTINUOUS_FOCUS_KEY);

        return is_string($value) ? $value : null;
    }

    public function setLevel(int $levelId): void
    {
        Session::put(self::LEVEL_KEY, $levelId);
    }

    public function levelId(): ?int
    {
        $id = Session::get(self::LEVEL_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function setIntakePeriodId(int $intakePeriodId): void
    {
        Session::put(self::INTAKE_KEY, $intakePeriodId);
    }

    public function intakePeriodId(): ?int
    {
        $id = Session::get(self::INTAKE_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function setRequiresFee(bool $requiresFee): void
    {
        Session::put(self::REQUIRES_FEE_KEY, $requiresFee);
    }

    public function requiresFee(): bool
    {
        $track = $this->getTrack();

        if ($track === ApplicationTrackEnum::Apprentice) {
            return false;
        }

        return (bool) Session::get(self::REQUIRES_FEE_KEY, false);
    }

    public function setTransferCollegeName(?string $collegeName): void
    {
        if ($collegeName === null || trim($collegeName) === '') {
            Session::forget(self::TRANSFER_COLLEGE_NAME_KEY);

            return;
        }

        Session::put(self::TRANSFER_COLLEGE_NAME_KEY, trim($collegeName));
    }

    public function transferCollegeName(): ?string
    {
        $value = Session::get(self::TRANSFER_COLLEGE_NAME_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function hasTransferCollegeName(): bool
    {
        return $this->transferCollegeName() !== null;
    }

    public function setProgramme(
        int $departmentId,
        int $departmentLevelId,
        int $courseId,
        int $modeOfStudyId,
    ): void {
        Session::put(self::DEPARTMENT_KEY, $departmentId);
        Session::put(self::DEPARTMENT_LEVEL_KEY, $departmentLevelId);
        Session::put(self::COURSE_KEY, $courseId);
        Session::put(self::MODE_KEY, $modeOfStudyId);
    }

    public function setOjetFormerStudent(
        string $identityType,
        ?string $idNumber,
        ?string $passportNumber,
        string $studentNumber,
        ?int $studentId = null,
    ): void {
        Session::put(self::OJET_IDENTITY_TYPE_KEY, $identityType);
        Session::put(self::OJET_STUDENT_NUMBER_KEY, $studentNumber);

        if ($idNumber !== null && $idNumber !== '') {
            Session::put(self::OJET_ID_NUMBER_KEY, $idNumber);
        } else {
            Session::forget(self::OJET_ID_NUMBER_KEY);
        }

        if ($passportNumber !== null && $passportNumber !== '') {
            Session::put(self::OJET_PASSPORT_NUMBER_KEY, $passportNumber);
        } else {
            Session::forget(self::OJET_PASSPORT_NUMBER_KEY);
        }

        if ($studentId !== null) {
            Session::put(self::OJET_STUDENT_ID_KEY, $studentId);
        } else {
            Session::forget(self::OJET_STUDENT_ID_KEY);
        }
    }

    public function clearOjetFormerStudent(): void
    {
        Session::forget([
            self::OJET_IDENTITY_TYPE_KEY,
            self::OJET_ID_NUMBER_KEY,
            self::OJET_PASSPORT_NUMBER_KEY,
            self::OJET_STUDENT_NUMBER_KEY,
            self::OJET_STUDENT_ID_KEY,
        ]);
    }

    public function ojetIdentityType(): ?string
    {
        $value = Session::get(self::OJET_IDENTITY_TYPE_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function ojetIdNumber(): ?string
    {
        $value = Session::get(self::OJET_ID_NUMBER_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function ojetPassportNumber(): ?string
    {
        $value = Session::get(self::OJET_PASSPORT_NUMBER_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function ojetStudentNumber(): ?string
    {
        $value = Session::get(self::OJET_STUDENT_NUMBER_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function ojetStudentId(): ?int
    {
        $id = Session::get(self::OJET_STUDENT_ID_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function hasResolvedOjetFormerStudent(): bool
    {
        return $this->ojetStudentNumber() !== null && $this->ojetIdentityType() !== null;
    }

    public function departmentId(): ?int
    {
        $id = Session::get(self::DEPARTMENT_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function departmentLevelId(): ?int
    {
        $id = Session::get(self::DEPARTMENT_LEVEL_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function courseId(): ?int
    {
        $id = Session::get(self::COURSE_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function modeOfStudyId(): ?int
    {
        $id = Session::get(self::MODE_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function acknowledgeInstructions(): void
    {
        Session::put(self::INSTRUCTIONS_KEY, true);
    }

    public function instructionsAcknowledged(): bool
    {
        return (bool) Session::get(self::INSTRUCTIONS_KEY, false);
    }

    public function markReadyForAccount(): void
    {
        Session::put(self::READY_FOR_ACCOUNT_KEY, true);
    }

    public function isReadyForAccount(): bool
    {
        return (bool) Session::get(self::READY_FOR_ACCOUNT_KEY, false);
    }

    public function hasTrack(): bool
    {
        return $this->getTrack() !== null;
    }

    public function hasLevelSelection(): bool
    {
        if ($this->getTrack() === null) {
            return false;
        }

        return $this->levelId() !== null && $this->intakePeriodId() !== null;
    }

    public function hasProgrammeSelection(): bool
    {
        if ($this->getTrack() === null) {
            return false;
        }

        return $this->departmentId() !== null
            && $this->departmentLevelId() !== null
            && $this->courseId() !== null
            && $this->modeOfStudyId() !== null;
    }

    /**
     * Whether guest eligibility steps are complete enough to create an account.
     */
    public function isCompleteForAccountCreation(): bool
    {
        if (! $this->hasTrack() || ! $this->hasLevelSelection() || ! $this->hasProgrammeSelection()) {
            return false;
        }

        if ($this->getTrack() === ApplicationTrackEnum::Transfer) {
            return $this->hasTransferCollegeName();
        }

        if ($this->stepperVariant() === 'ojet' && ! $this->hasResolvedOjetFormerStudent()) {
            return false;
        }

        return true;
    }

    /**
     * Stepper variant for the current intent.
     *
     * @return 'regular'|'sdp'|'ojet'|'apprentice'|'transfer'
     */
    public function stepperVariant(): string
    {
        $track = $this->getTrack();

        if ($track === ApplicationTrackEnum::Apprentice) {
            return 'apprentice';
        }

        if ($track === ApplicationTrackEnum::Transfer) {
            return 'transfer';
        }

        if ($track === ApplicationTrackEnum::Continuous) {
            return $this->continuousFocus() === 'sdp' ? 'sdp' : 'ojet';
        }

        return 'regular';
    }

    public function clearProgramme(): void
    {
        Session::forget([
            self::DEPARTMENT_KEY,
            self::DEPARTMENT_LEVEL_KEY,
            self::COURSE_KEY,
            self::MODE_KEY,
            self::READY_FOR_ACCOUNT_KEY,
        ]);
        $this->clearOjetFormerStudent();
    }

    public function clearLevelAndBelow(): void
    {
        Session::forget([
            self::LEVEL_KEY,
            self::INTAKE_KEY,
            self::READY_FOR_ACCOUNT_KEY,
            self::REQUIRES_FEE_KEY,
        ]);
        $this->clearProgramme();
    }

    public function clear(): void
    {
        Session::forget([
            self::TRACK_KEY,
            self::LEVEL_KEY,
            self::INTAKE_KEY,
            self::CONTINUOUS_FOCUS_KEY,
            self::DEPARTMENT_KEY,
            self::DEPARTMENT_LEVEL_KEY,
            self::COURSE_KEY,
            self::MODE_KEY,
            self::INSTRUCTIONS_KEY,
            self::READY_FOR_ACCOUNT_KEY,
            self::REQUIRES_FEE_KEY,
            self::TRANSFER_COLLEGE_NAME_KEY,
            self::OJET_IDENTITY_TYPE_KEY,
            self::OJET_ID_NUMBER_KEY,
            self::OJET_PASSPORT_NUMBER_KEY,
            self::OJET_STUDENT_NUMBER_KEY,
            self::OJET_STUDENT_ID_KEY,
        ]);
    }

    /**
     * Copy guest intent into the authenticated application session keys.
     */
    public function promoteToApplicationSession(ApplicationTrackSession $trackSession): void
    {
        $track = $this->getTrack();

        if ($track === null) {
            return;
        }

        $trackSession->set($track);

        if ($this->levelId() !== null) {
            $trackSession->setLevel($this->levelId());
            Session::put(ApplicationTrackSession::LEVEL_KEY, $this->levelId());
            Session::put('application.level_id', $this->levelId());
        }

        if ($this->intakePeriodId() !== null) {
            $trackSession->setIntakePeriodId($this->intakePeriodId());
        }

        if ($this->continuousFocus() !== null) {
            Session::put('application.continuous_focus', $this->continuousFocus());
        }

        Session::put('application.requires_fee', $this->requiresFee());

        if ($this->departmentId() !== null) {
            Session::put('application.department_id', $this->departmentId());
        }

        if ($this->departmentLevelId() !== null) {
            Session::put('application.department_level_id', $this->departmentLevelId());
        }

        if ($this->courseId() !== null) {
            Session::put('application.course_id', $this->courseId());
        }

        if ($this->modeOfStudyId() !== null) {
            Session::put('application.mode_of_study_id', $this->modeOfStudyId());
        }

        if ($this->transferCollegeName() !== null) {
            $trackSession->setTransferCollegeName($this->transferCollegeName());
        }

        if ($this->hasResolvedOjetFormerStudent()) {
            Session::put('application.ojet_identity_type', $this->ojetIdentityType());
            Session::put('application.ojet_student_number', $this->ojetStudentNumber());

            if ($this->ojetIdNumber() !== null) {
                Session::put('application.ojet_id_number', $this->ojetIdNumber());
            }

            if ($this->ojetPassportNumber() !== null) {
                Session::put('application.ojet_passport_number', $this->ojetPassportNumber());
            }

            if ($this->ojetStudentId() !== null) {
                Session::put('application.ojet_student_id', $this->ojetStudentId());
            }
        }
    }

    /**
     * @return array{
     *     track: string|null,
     *     trackLabel: string|null,
     *     continuousFocus: string|null,
     *     levelId: int|null,
     *     intakePeriodId: int|null,
     *     departmentId: int|null,
     *     departmentLevelId: int|null,
     *     courseId: int|null,
     *     modeOfStudyId: int|null,
     *     transferCollegeName: string|null,
     *     readyForAccount: bool,
     *     requiresFee: bool,
     *     stepperVariant: string
     * }
     */
    public function summary(): array
    {
        $track = $this->getTrack();

        return [
            'track' => $track?->value,
            'trackLabel' => $track?->label(),
            'continuousFocus' => $this->continuousFocus(),
            'levelId' => $this->levelId(),
            'intakePeriodId' => $this->intakePeriodId(),
            'departmentId' => $this->departmentId(),
            'departmentLevelId' => $this->departmentLevelId(),
            'courseId' => $this->courseId(),
            'modeOfStudyId' => $this->modeOfStudyId(),
            'transferCollegeName' => $this->transferCollegeName(),
            'readyForAccount' => $this->isReadyForAccount(),
            'requiresFee' => $this->requiresFee(),
            'stepperVariant' => $this->stepperVariant(),
        ];
    }
}
