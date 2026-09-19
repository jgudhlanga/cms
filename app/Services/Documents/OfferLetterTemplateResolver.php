<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Institution\OfferLetterTemplate;
use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OfferLetterTemplateResolver
{
    public const int SCORE_DEPARTMENT = 8;

    public const int SCORE_LEVEL = 4;

    public const int SCORE_COURSE = 2;

    public const int SCORE_MODE = 1;

    public function resolve(StudentApplication $application): OfferLetterTemplate
    {
        $application->loadMissing([
            'intakePeriod',
            'institutionDepartment',
            'departmentLevel.level',
            'departmentCourse.course',
            'modeOfStudy',
        ]);

        $candidates = OfferLetterTemplate::query()
            ->where('tenant_id', $application->tenant_id)
            ->where('intake_period_id', $application->intake_period_id)
            ->with(['institutionDepartments:id', 'levels:id'])
            ->get();

        /** @var Collection<int, OfferLetterTemplate> $eligible */
        $eligible = $candidates->filter(
            fn (OfferLetterTemplate $template): bool => $this->matches($template, $application),
        );

        if ($eligible->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(OfferLetterTemplate::class);
        }

        return $this->highestScore($eligible, $application);
    }

    /**
     * @param  Collection<int, OfferLetterTemplate>  $templates
     */
    private function highestScore(Collection $templates, StudentApplication $application): OfferLetterTemplate
    {
        $scored = $templates
            ->map(fn (OfferLetterTemplate $template): array => [
                'template' => $template,
                'score' => $this->score($template),
            ])
            ->sortByDesc('score')
            ->values();

        $maxScore = (int) $scored->max('score');
        $winners = $scored->where('score', $maxScore)->values();

        if ($winners->count() > 1) {
            $withOverride = $winners->filter(
                fn (array $row): bool => $row['template']->tuition_override !== null,
            );

            if ($withOverride->count() === 1) {
                $winners = $withOverride->values();
            } else {
                Log::warning('Ambiguous offer letter templates with the same specificity.', [
                    'student_application_id' => $application->id,
                    'score' => $maxScore,
                    'template_ids' => $winners->pluck('template.id')->all(),
                ]);
            }
        }

        /** @var OfferLetterTemplate $template */
        $template = $winners->sortByDesc(fn (array $row): int => (int) $row['template']->id)->first()['template'];

        return $template;
    }

    private function matches(OfferLetterTemplate $template, StudentApplication $application): bool
    {
        $departmentIds = $template->departmentIds();
        $levelIds = $template->levelIds();
        $applicationDepartmentId = (int) $application->institution_department_id;
        $applicationLevelId = (int) ($application->departmentLevel?->level_id ?? 0);

        $departmentMatches = $departmentIds === []
            || ($applicationDepartmentId > 0 && in_array($applicationDepartmentId, $departmentIds, true));
        $levelMatches = $levelIds === []
            || ($applicationLevelId > 0 && in_array($applicationLevelId, $levelIds, true));

        return $departmentMatches
            && $levelMatches
            && $this->dimensionMatches($template->course_id, $application->departmentCourse?->course_id)
            && $this->dimensionMatches($template->mode_of_study_id, $application->mode_of_study_id);
    }

    private function dimensionMatches(?int $templateId, mixed $applicationId): bool
    {
        if ($templateId === null || $templateId < 1) {
            return true;
        }

        $applicationValue = $applicationId !== null ? (int) $applicationId : 0;

        return $applicationValue > 0 && $templateId === $applicationValue;
    }

    private function score(OfferLetterTemplate $template): int
    {
        $score = 0;

        if ($template->departmentIds() !== []) {
            $score += self::SCORE_DEPARTMENT;
        }

        if ($template->levelIds() !== []) {
            $score += self::SCORE_LEVEL;
        }

        if ($this->isFilled($template->course_id)) {
            $score += self::SCORE_COURSE;
        }

        if ($this->isFilled($template->mode_of_study_id)) {
            $score += self::SCORE_MODE;
        }

        return $score;
    }

    private function isFilled(?int $id): bool
    {
        return $id !== null && $id > 0;
    }
}
