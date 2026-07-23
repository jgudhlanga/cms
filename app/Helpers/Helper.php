<?php

namespace App\Helpers;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Staff;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Tenants\Tenant;
use App\Services\Students\IntakePeriodOrderingService;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Helper
{
    public static function generateModelUniqueNumber(Model $model, string $prefix, string $suffix): string
    {
        $id = (int) $model->id > 0 ? $model->id : $model::max('id') + 1;

        return $prefix.$id.$suffix;
    }

    public static function generateStudentNumber(StudentApplication $program): string
    {
        $student = $program->student;
        $department = $program->institutionDepartment;
        // next year in 2-digit format
        $intakePeriod = $program->intakePeriod;
        $year = $intakePeriod ? Carbon::parse($intakePeriod->calendar_year)->format('y') : Carbon::now()->addYear()->format('y');

        // department code (uppercased)
        $departmentCode = strtoupper($department->department_code);

        // always prefix the student ID with a 0
        $studentIdPadded = '0'.$student->id;

        // college code
        $collegeCode = 'HP';

        return $year.$departmentCode.$studentIdPadded.$collegeCode;
    }

    public static function lookupLegacyStudentNumber(string $studentIdentity): ?string
    {
        $filePath = storage_path('data/legacy-students.csv');
        if (! file_exists($filePath)) {
            return 'CSV file not found';
        }

        // sanitize input identity (remove dashes)
        $studentIdentity = strtoupper(str_replace('-', '', trim($studentIdentity)));
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);

        // Remove empty values at end of header
        $header = array_filter($header);

        $searchField = 'id_number';
        $valueField = 'student_number';

        while (($row = fgetcsv($file)) !== false) {
            // Remove trailing empty values to match header count
            $row = array_slice($row, 0, count($header));
            $student = @array_combine($header, $row);

            if (! $student) {
                continue; // skip malformed rows
            }

            // sanitize CSV id_number
            $csvId = strtoupper(str_replace('-', '', trim($student[$searchField] ?? '')));

            if ($csvId === $studentIdentity) {
                fclose($file);

                return $student[$valueField] ?? null;
            }
        }

        fclose($file);

        return null;
    }

    public static function formatDate($stringDate): string
    {
        return Carbon::parse($stringDate)->format('Y-m-d');
    }

    public static function encrypt(string $string): string
    {
        return Crypt::encryptString($string);
    }

    public static function decrypt(string $string): string
    {
        try {
            return Crypt::decryptString($string);
        } catch (DecryptException $e) {
            throw new \RuntimeException('Decryption failed.', 0, $e);
        }
    }

    public static function mask(string $string): string
    {
        return Str::of($string)->mask('*', 2, -2);
    }

    public static function generatePasswordFromName(string $firstName, string $lastName): string
    {
        $firstPart = ucfirst(substr($firstName, 0, 3));  // At least one uppercase
        $lastPart = strtolower(substr($lastName, 0, 3)); // Lowercase
        $number = rand(10, 99);                         // At least one digit
        $specialChars = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $special = $specialChars[array_rand($specialChars)]; // Special character

        // Concatenate to ensure all requirements are met
        $basePassword = "{$firstPart}{$lastPart}{$number}{$special}";

        // Ensure minimum length of 8 characters
        while (strlen($basePassword) < 8) {
            $basePassword .= chr(rand(97, 122)); // add random lowercase letters if needed
        }

        // Shuffle for added security
        return str_shuffle($basePassword);
    }

    public static function getTenant(): ?Model
    {
        return Tenant::where('name', TenantEnum::HARARE_POLY->value)->firstOrFail();
    }

    public static function getActiveStatus(): ?Model
    {
        return Status::where('title', StatusEnum::ACTIVE->value)->firstOrFail();
    }

    public static function resolveIntakePeriod()
    {
        static $cachedIntakePeriod;

        // If request explicitly provides intake_period_id, always use it (no caching)
        if (request()->filled('intake_period_id') && request()->intake_period_id > 0) {
            return IntakePeriod::findOrFail(request()->intake_period_id);
        }

        // Otherwise fallback to cached period
        if ($cachedIntakePeriod) {
            return $cachedIntakePeriod;
        }

        $ordering = app(IntakePeriodOrderingService::class);
        $default = $ordering->defaultAdminIntakePeriod();

        if ($default !== null) {
            return $cachedIntakePeriod = $default;
        }

        // Cache the fallback only once (legacy: any intake by end_date)
        return $cachedIntakePeriod = IntakePeriod::orderByDesc('end_date')->firstOrFail();
    }

    public static function resolveAcademicCalendar(): AcademicCalendar
    {
        static $cachedAcademicCalendar;

        if (request()->filled('academic_calendar_id') && request()->integer('academic_calendar_id') > 0) {
            return AcademicCalendar::query()
                ->semesters()
                ->findOrFail(request()->integer('academic_calendar_id'));
        }

        if ($cachedAcademicCalendar instanceof AcademicCalendar) {
            return $cachedAcademicCalendar;
        }

        return $cachedAcademicCalendar = AcademicCalendar::resolveSemesterForDate();
    }

    public static function resolveUserDepartments(): ?array
    {
        $departments = [];
        if (self::isDepartmentUser()) {
            $user = auth()->user();
            $staffProfile = $user->staffProfile;
            if ($staffProfile && $staffProfile instanceof Staff) {
                $departments = $staffProfile
                    ->institutionDepartments()
                    ->pluck('institution_departments.id')
                    ->toArray();
            }
        }

        return $departments;
    }

    public static function isDepartmentUser(): bool
    {
        $user = auth()->user();

        return $user && $user->can('viewOnlyOwnDepartment:departments');
    }

    public static function hasAccessToNonAcademicDepartments(): bool
    {
        $departments = self::resolveUserDepartments();
        if (empty($departments)) {
            return false;
        }
        $nonAcademicCount = InstitutionDepartment::whereIn('id', $departments)
            ->whereHas('department', function ($query) {
                $query->where('is_academic', false);
            })->count();

        return $nonAcademicCount > 0;
    }

    public static function resolveModeOfStudy(): Model|Collection|null
    {
        if (request()->filled('mode_of_study_id') && request()->mode_of_study_id > 0) {
            return ModeOfStudy::findOrFail(request()->mode_of_study_id);
        }

        return ModeOfStudy::where('name', ModeOfStudyEnum::FULL_TIME->value)->first();
    }

    public static function initializeProgramWorkflow($program): void
    {
        $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition(
            $program->institution_department_id,
            1
        );

        $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition(
            $program->institution_department_id,
            2
        );

        if ($stepOne) {
            $program->update(['department_application_step_id' => $stepOne->id]);
        }

        if ($stepTwo) {
            $program->update(['department_application_step_id' => $stepTwo->id]);
        }
    }

    public static function generateAndAssignStudentNumber(Student $student, $program): void
    {
        $studentNumber = Helper::generateStudentNumber($program);
        $student->update(['student_number' => $studentNumber]);
    }

    public static function generateRandomCode(string $prefix): string
    {
        $randomSegment = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 8));

        return "{$prefix}-{$randomSegment}";
    }
}

if (! function_exists('isValidZimbabweanId')) {
    function isValidZimbabweanId(?string $idNumber): bool
    {
        if (! $idNumber) {
            return false;
        }

        $idNumber = strtoupper(trim($idNumber));

        return preg_match(
            '/^\d{2}-\d{5,7}[A-Z]\d{2}$/',
            $idNumber
        ) === 1;
    }
}
