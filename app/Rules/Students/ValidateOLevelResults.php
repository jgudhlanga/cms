<?php

namespace App\Rules\Students;

use App\Models\Institution\Grade;
use App\Services\Students\OLevelRequirementResolver;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;

class ValidateOLevelResults
{
    private const int MAX_DISTINCT_EXAM_YEARS = 3;

    private const int MAX_HISTORY = 20;

    private const int MIN_AGE_AT_EXAM = 14;

    private const array ALLOWED_SITTINGS = ['june', 'november', 'other'];

    public function __construct(
        private readonly OLevelRequirementResolver $requirementResolver,
    ) {}

    public function validate(Request $request, Validator $validator): void
    {
        $departmentLevelId = $request->filled('level_id') ? $request->integer('level_id') : null;
        $departmentCourseId = $request->filled('course_id') ? $request->integer('course_id') : null;
        $requirement = $this->requirementResolver->resolve($departmentLevelId, $departmentCourseId);

        if ($requirement === null || ! $requirement->is_o_level_required) {
            return;
        }

        $mainSubjects = $request->input('o_level_subject_ids', []);
        $mainYears = $request->input('o_level_years', []);
        $mainSittings = $request->input('o_level_sittings', []);
        $otherSubjects = $request->input('o_level_other_subject_ids', []);
        $otherGrades = $request->input('o_level_other_grade_ids', []);
        $otherYears = $request->input('o_level_other_years', []);
        $otherSittings = $request->input('o_level_other_sittings', []);

        if (! is_array($mainSubjects)) {
            $validator->errors()->add('o_level', __('trans.o_level_validation_main_subjects_required'));

            return;
        }

        $mainSubjectCount = (int) $requirement->main_subjects_count;
        $otherSubjectCount = (int) $requirement->other_subjects_count;
        $allowedGradeIds = Grade::query()->where('position', '<=', 3)->pluck('id')->all();
        $dateOfBirth = $request->input('date_of_birth');

        if (count($mainSubjects) < $mainSubjectCount) {
            $validator->errors()->add(
                'o_level',
                __('trans.o_level_validation_main_subjects_count', ['count' => $mainSubjectCount]),
            );
        }

        foreach ($mainSubjects as $subjectId => $gradeId) {
            $label = __('trans.o_level_validation_main_subject', ['id' => $subjectId]);
            $this->validateGrade($validator, $gradeId, $allowedGradeIds, $label);
            $year = $this->arrayValue($mainYears, $subjectId);
            $this->validateYear($validator, $year, $dateOfBirth, $label);
            $sitting = $this->extractSittingValue($this->arrayValue($mainSittings, $subjectId));
            if ($sitting === null || ! in_array($sitting, self::ALLOWED_SITTINGS, true)) {
                $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_sitting_required'));
            }
        }

        if (! is_array($otherSubjects)) {
            $otherSubjects = [];
        }

        if (count($otherSubjects) < $otherSubjectCount) {
            $validator->errors()->add(
                'o_level',
                __('trans.o_level_validation_other_subjects_count', ['count' => $otherSubjectCount]),
            );
        }

        $seenSubjectIds = [];
        foreach ($otherSubjects as $key => $subject) {
            $label = __('trans.o_level_validation_other_subject', ['number' => $key]);
            $subjectId = is_array($subject) ? ($subject['value'] ?? null) : $subject;
            if (! $subjectId) {
                $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_subject_required'));

                continue;
            }
            if (in_array((int) $subjectId, $seenSubjectIds, true)) {
                $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_duplicate_subject'));
            }
            $seenSubjectIds[] = (int) $subjectId;

            $gradeId = $this->arrayValue($otherGrades, $key);
            $this->validateGrade($validator, $gradeId, $allowedGradeIds, $label);

            $year = $this->arrayValue($otherYears, $key);
            $this->validateYear($validator, $year, $dateOfBirth, $label);

            $sitting = $this->extractSittingValue($this->arrayValue($otherSittings, $key));
            if ($sitting === null || ! in_array($sitting, self::ALLOWED_SITTINGS, true)) {
                $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_sitting_required'));
            }
        }

        $distinctYears = $this->collectDistinctYears($mainYears, $otherYears);
        if (count($distinctYears) > self::MAX_DISTINCT_EXAM_YEARS) {
            $validator->errors()->add(
                'o_level',
                __('trans.portal_o_level_max_exam_years_exceeded', ['max' => self::MAX_DISTINCT_EXAM_YEARS]),
            );
        }
    }

    private function validateGrade(Validator $validator, mixed $gradeId, array $allowedGradeIds, string $label): void
    {
        if (! $gradeId || ! in_array((int) $gradeId, $allowedGradeIds, true)) {
            $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_grade_required'));
        }
    }

    private function validateYear(Validator $validator, mixed $year, mixed $dateOfBirth, string $label): void
    {
        $normalized = $this->normalizeYear($year);
        if ($normalized === null) {
            $validator->errors()->add('o_level', "{$label}: ".__('trans.o_level_validation_year_required'));

            return;
        }

        [$minYear, $maxYear] = $this->yearBounds($dateOfBirth);
        if ($normalized < $minYear || $normalized > $maxYear) {
            $validator->errors()->add(
                'o_level',
                "{$label}: ".__('trans.o_level_validation_year_range', ['min' => $minYear, 'max' => $maxYear]),
            );
        }
    }

    private function normalizeYear(mixed $year): ?int
    {
        if ($year === null || $year === '') {
            return null;
        }

        $yearString = trim((string) $year);
        if (! preg_match('/^\d{4}/', $yearString)) {
            return null;
        }

        return (int) substr($yearString, 0, 4);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function yearBounds(mixed $dateOfBirth): array
    {
        $maxYear = (int) now()->format('Y');
        $minYear = $maxYear - self::MAX_HISTORY;

        if ($dateOfBirth) {
            try {
                $birthYear = (int) Carbon::parse($dateOfBirth)->format('Y');
                $minYear = max($minYear, $birthYear + self::MIN_AGE_AT_EXAM);
            } catch (\Throwable) {
                // keep default bounds
            }
        }

        return [$minYear, $maxYear];
    }

    private function extractSittingValue(mixed $sitting): ?string
    {
        if (is_array($sitting)) {
            $value = $sitting['value'] ?? null;

            return $value !== null ? (string) $value : null;
        }

        return $sitting !== null ? (string) $sitting : null;
    }

    private function arrayValue(mixed $array, int|string $key): mixed
    {
        if (! is_array($array)) {
            return null;
        }

        return $array[$key] ?? $array[(string) $key] ?? null;
    }

    /**
     * @return list<int>
     */
    private function collectDistinctYears(mixed $mainYears, mixed $otherYears): array
    {
        $years = [];
        foreach ([$mainYears, $otherYears] as $map) {
            if (! is_array($map)) {
                continue;
            }
            foreach ($map as $year) {
                $normalized = $this->normalizeYear($year);
                if ($normalized !== null) {
                    $years[$normalized] = true;
                }
            }
        }

        return array_keys($years);
    }
}
