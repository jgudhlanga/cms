<?php

namespace App\Services\AcademicCalendars;

class CourseWorkAggregationService
{
    public const COURSEWORK_CAP = 60;

    /**
     * @param  list<array{id: int, name: string, weightPercent: int|null}>  $assessmentTypes
     * @param  list<array{assessmentTypeId: int, assessmentTypeName?: string, mark: int|null, remark: string|null}>  $assessments
     * @return array{
     *     components: list<array{assessmentTypeId: int, assessmentTypeName: string, rawMark: int|null, weightPercent: int, weightedMark: float|null}>,
     *     courseWorkTotal60: float|null,
     *     isComplete: bool,
     *     remark: string|null
     * }
     */
    public function aggregateStudentModule(array $assessmentTypes, array $assessments): array
    {
        if ($assessmentTypes === []) {
            return [
                'components' => [],
                'courseWorkTotal60' => null,
                'isComplete' => false,
                'remark' => null,
            ];
        }

        $weightsByTypeId = $this->resolvedWeightsByTypeId($assessmentTypes);
        $marksByTypeId = collect($assessments)->keyBy('assessmentTypeId');

        $components = [];
        $total = 0.0;
        $isComplete = true;
        $remark = null;

        foreach ($assessmentTypes as $type) {
            $typeId = (int) $type['id'];
            $assessment = $marksByTypeId->get($typeId);
            $rawMark = $assessment !== null && $assessment['mark'] !== null
                ? (int) $assessment['mark']
                : null;
            $weightPercent = $weightsByTypeId[$typeId] ?? 0;

            $weightedMark = $rawMark !== null
                ? round($rawMark * $weightPercent / 100, 2)
                : null;

            if ($rawMark === null) {
                $isComplete = false;
            } elseif ($weightedMark !== null) {
                $total += $weightedMark;
            }

            $assessmentRemark = $assessment['remark'] ?? null;
            if ($remark === null && is_string($assessmentRemark) && trim($assessmentRemark) !== '') {
                $remark = trim($assessmentRemark);
            }

            $components[] = [
                'assessmentTypeId' => $typeId,
                'assessmentTypeName' => (string) ($assessment['assessmentTypeName'] ?? $type['name']),
                'rawMark' => $rawMark,
                'weightPercent' => $weightPercent,
                'weightedMark' => $weightedMark,
            ];
        }

        $courseWorkTotal60 = $isComplete
            ? round(min(self::COURSEWORK_CAP, $total), 2)
            : null;

        return [
            'components' => $components,
            'courseWorkTotal60' => $courseWorkTotal60,
            'isComplete' => $isComplete,
            'remark' => $remark,
        ];
    }

    /**
     * @param  list<array{id: int, name: string, weightPercent: int|null}>  $assessmentTypes
     * @return array<int, int>
     */
    private function resolvedWeightsByTypeId(array $assessmentTypes): array
    {
        $allHaveWeight = collect($assessmentTypes)->every(
            fn (array $type): bool => $type['weightPercent'] !== null && (int) $type['weightPercent'] > 0,
        );

        if ($allHaveWeight) {
            return collect($assessmentTypes)
                ->mapWithKeys(fn (array $type): array => [(int) $type['id'] => (int) $type['weightPercent']])
                ->all();
        }

        $count = count($assessmentTypes);
        if ($count === 0) {
            return [];
        }

        $equalWeight = (int) floor(self::COURSEWORK_CAP / $count);
        $remainder = self::COURSEWORK_CAP - ($equalWeight * $count);

        $weights = [];
        foreach ($assessmentTypes as $index => $type) {
            $weight = $equalWeight + ($index < $remainder ? 1 : 0);
            $weights[(int) $type['id']] = $weight;
        }

        return $weights;
    }
}
