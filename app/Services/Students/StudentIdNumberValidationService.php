<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Maintenance\Students\FaultyStudentIdNumberAnalysis;
use Illuminate\Contracts\Auth\Authenticatable;

class StudentIdNumberValidationService
{
    public function __construct(
        private readonly FaultyStudentIdNumberAnalysis $faultyStudentIdNumberAnalysis,
    ) {}

    /**
     * @return array{
     *     idNumberValid: bool|null,
     *     suggestedIdNumber: string|null,
     *     idNumberRectificationStatus: string|null,
     *     idNumberConflict: array<string, mixed>|null
     * }
     */
    public function resolve(Student $student, ?Authenticatable $viewer = null): array
    {
        if (! $this->isZimbabweanIdType($student)) {
            return [
                'idNumberValid' => null,
                'suggestedIdNumber' => null,
                'idNumberRectificationStatus' => null,
                'idNumberConflict' => null,
            ];
        }

        $idNumber = (string) ($student->id_number ?? '');

        if (ZimbabweanIdNumber::isValid($idNumber)) {
            return [
                'idNumberValid' => true,
                'suggestedIdNumber' => null,
                'idNumberRectificationStatus' => null,
                'idNumberConflict' => null,
            ];
        }

        $analysis = $this->faultyStudentIdNumberAnalysis->analyze($student);
        $conflict = $analysis['conflict'] ?? null;

        if (is_array($conflict) && ! ($viewer?->can('root:manage') ?? false)) {
            unset($conflict['mergePreviewUrl']);
        }

        return [
            'idNumberValid' => false,
            'suggestedIdNumber' => $analysis['suggestedIdNumber'] ?? null,
            'idNumberRectificationStatus' => $analysis['rectificationStatus'] ?? null,
            'idNumberConflict' => $conflict,
        ];
    }

    public function isZimbabweanIdType(Student $student): bool
    {
        return $student->isZimbabwean();
    }

    public function hasValidZimbabweanId(Student $student): bool
    {
        if (! $this->isZimbabweanIdType($student)) {
            return true;
        }

        return ZimbabweanIdNumber::isValid((string) ($student->id_number ?? ''));
    }
}
