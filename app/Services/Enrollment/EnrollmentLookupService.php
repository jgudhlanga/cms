<?php

namespace App\Services\Enrollment;

use App\Helpers\Helper;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Users\User;

class EnrollmentLookupService
{
    public static function normalizeNationalId(string $idNumber): string
    {
        $value = strtoupper(trim(str_replace(' ', '', $idNumber)));
        $raw = preg_replace('/[^A-Z0-9]/', '', $value) ?? '';

        if (strlen($raw) <= 2) {
            return $value;
        }

        return substr($raw, 0, 2).'-'.substr($raw, 2);
    }

    public static function normalizePassportNumber(string $passportNumber): string
    {
        return strtoupper(trim(str_replace(' ', '', $passportNumber)));
    }

    public static function normalizeStudentNumber(string $studentNumber): string
    {
        return strtoupper(trim(str_replace(' ', '', $studentNumber)));
    }

    /**
     * @return array{found: bool, maskedName: string|null, maskedEmail: string|null, loginEmail: string|null}
     */
    public function checkNationalIdDuplicate(string $idNumber): array
    {
        $student = $this->findStudentByNationalId($idNumber);

        if (! $student instanceof Student) {
            return $this->notFoundPayload();
        }

        return $this->foundPayload($student);
    }

    /**
     * @return array{found: bool, maskedName: string|null, maskedEmail: string|null, loginEmail: string|null}
     */
    public function checkPassportDuplicate(string $passportNumber): array
    {
        $student = $this->findStudentByPassport($passportNumber);

        if (! $student instanceof Student) {
            return $this->notFoundPayload();
        }

        return $this->foundPayload($student);
    }

    /**
     * @return array{found: bool, maskedName: string|null, maskedEmail: string|null, loginEmail: string|null}
     */
    public function lookupReturning(string $type, string $value): array
    {
        $student = match ($type) {
            'student_number' => $this->findStudentByStudentNumber($value),
            default => $this->findStudentByNationalId($value),
        };

        if (! $student instanceof Student) {
            return $this->notFoundPayload();
        }

        return $this->foundPayload($student);
    }

    public function nationalIdExists(string $idNumber): bool
    {
        if ($this->findStudentByNationalId($idNumber) instanceof Student) {
            return true;
        }

        $normalized = self::normalizeNationalId($idNumber);

        return ApplicationFee::query()
            ->where(function ($query) use ($normalized) {
                $query->where('id_number', $normalized)
                    ->orWhere('id_number', str_replace('-', '', $normalized));
            })
            ->whereNull('student_application_id')
            ->exists();
    }

    public function passportExists(string $passportNumber): bool
    {
        if ($this->findStudentByPassport($passportNumber) instanceof Student) {
            return true;
        }

        $normalized = self::normalizePassportNumber($passportNumber);

        return ApplicationFee::query()
            ->where('passport_number', $normalized)
            ->whereNull('student_application_id')
            ->exists();
    }

    public function findStudentByNationalId(string $idNumber): ?Student
    {
        $normalized = self::normalizeNationalId($idNumber);
        $compact = str_replace('-', '', $normalized);

        return Student::query()
            ->where(function ($query) use ($normalized, $compact) {
                $query->where('id_number', $normalized)
                    ->orWhere('id_number', $compact)
                    ->orWhereRaw("REPLACE(id_number, '-', '') = ?", [$compact]);
            })
            ->with('user')
            ->first();
    }

    public function findStudentByNationalIdUnscoped(string $idNumber, ?int $excludingStudentId = null): ?Student
    {
        $normalized = self::normalizeNationalId($idNumber);
        $compact = strtoupper(str_replace('-', '', $normalized));

        $query = Student::query()
            ->withTrashed()
            ->whereNotNull('id_number')
            ->where(function ($query) use ($normalized, $compact) {
                $query->where('id_number', $normalized)
                    ->orWhere('id_number', $compact)
                    ->orWhereRaw('UPPER(TRIM(REPLACE(id_number, "-", ""))) = ?', [$compact]);
            });

        if ($excludingStudentId !== null) {
            $query->where('id', '!=', $excludingStudentId);
        }

        return $query->with('user')->first();
    }

    public function findStudentByPassport(string $passportNumber): ?Student
    {
        $normalized = self::normalizePassportNumber($passportNumber);

        return Student::query()
            ->where('passport_number', $normalized)
            ->with('user')
            ->first();
    }

    public function findStudentByStudentNumber(string $studentNumber): ?Student
    {
        $normalized = self::normalizeStudentNumber($studentNumber);

        return Student::query()
            ->where('student_number', $normalized)
            ->with('user')
            ->first();
    }

    /**
     * @return array{found: bool, maskedName: string|null, maskedEmail: string|null, loginEmail: string|null}
     */
    private function foundPayload(Student $student): array
    {
        $user = $student->user;

        if (! $user instanceof User) {
            return $this->notFoundPayload();
        }

        return [
            'found' => true,
            'maskedName' => Helper::mask($user->full_name),
            'maskedEmail' => $this->maskEmail($user->email),
            'loginEmail' => null,
        ];
    }

    /**
     * @return array{found: bool, maskedName: string|null, maskedEmail: string|null, loginEmail: string|null}
     */
    private function notFoundPayload(): array
    {
        return [
            'found' => false,
            'maskedName' => null,
            'maskedEmail' => null,
            'loginEmail' => null,
        ];
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return Helper::mask($email);
        }

        [$local, $domain] = explode('@', $email, 2);

        return Helper::mask($local).'@'.$domain;
    }
}
