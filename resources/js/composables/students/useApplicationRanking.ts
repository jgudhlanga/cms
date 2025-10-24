import { useClassListStore } from '@/store/enrolments/useClassListStore';
import type { DepartmentLevel } from '@/types/department-meta-data';
import type { EnrolmentApplication, OLeveResult } from '@/types/enrolments';
import { computed, onMounted, Ref, watch } from 'vue';

/**
 * Centralized logic for computing and sorting enrolment applications
 * based on academic results, payment status, and department requirements.
 */
export function useApplicationRanking(level: DepartmentLevel, applications: Ref<EnrolmentApplication[]>, classSize: Ref<number>) {
    const classListStore = useClassListStore();
    /**
     * Required subjects and requirements
     */
    const levelRequirements = computed(() => level?.relationships?.requirement);
    const requirementSubjects = computed(() => level?.relationships?.requirement?.relationships?.subjects);

    /**
     * Get main subject grade
     */
    const getMainSubjectGrade = (results: OLeveResult[], subjectId: string | number): OLeveResult | null | undefined => {
        return results.find((r) => String(r.subjectId) === String(subjectId));
    };

    /**
     * Get other subject grades (top N)
     */
    const getOtherSubjectGrades = (results: OLeveResult[]): Record<number, OLeveResult> => {
        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;

        const requiredIds = requiredSubjects.map((s: any) => String(s.id));
        const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));

        const gradeOrder: Record<string, number> = { A: 1, B: 2, C: 3 };

        const sortedOthers = otherSubjects.sort((a, b) => {
            const aVal = gradeOrder[a.grade?.trim()] || 999;
            const bVal = gradeOrder[b.grade?.trim()] || 999;
            return aVal - bVal;
        });

        const grades: Record<number, OLeveResult> = {};
        for (let i = 0; i < otherSubjectsCount; i++) {
            grades[i + 1] = sortedOthers[i] || ({ grade: '---', subject: '---' } as OLeveResult);
        }

        return grades;
    };

    /**
     * Helper: compute numeric score per grade considering exam sitting
     */
    const getGradeScore = (grade: string, examYear: string, firstExamYear: string, uniqueYears: string[]) => {
        const trimmed = grade?.trim() || 'N/A';
        if (trimmed === 'N/A' || trimmed === '---') return 9;

        const sittingIndex = uniqueYears.findIndex((y) => y === examYear);
        const offset = sittingIndex >= 0 ? sittingIndex : 0;

        switch (trimmed) {
            case 'A':
                return 1 + offset;
            case 'B':
                return 2 + offset;
            case 'C':
                return 3 + offset;
            default:
                return 9;
        }
    };

    /**
     * Compute faulty applications
     */
    const faultyApplications = computed(() => {
        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;
        const requiredIds = requiredSubjects.map((s: any) => String(s.id));

        return applications.value.filter((app) => {
            const results: OLeveResult[] = app.academicResults || [];
            const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

            const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => a - b);
            const firstExamYear = uniqueYears[0] ?? 0;

            const mainScores = requiredIds.map((sid) => {
                const r = results.find((res) => String(res.subjectId) === sid);
                if (!r) return 9;
                return getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears);
            });

            const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));
            const sortedOthers = otherSubjects
                .sort(
                    (a, b) =>
                        getGradeScore(a.grade || 'N/A', a.examYear, firstExamYear, uniqueYears) -
                        getGradeScore(b.grade || 'N/A', b.examYear, firstExamYear, uniqueYears),
                )
                .slice(0, otherSubjectsCount);

            while (sortedOthers.length < otherSubjectsCount) {
                sortedOthers.push({ grade: 'N/A', examYear: firstExamYear } as OLeveResult);
            }

            const otherScores = sortedOthers.map((r) => getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears));

            const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);
            return hasInvalidGrade || hasNoPayment;
        });
    });

    /**
     * Compute and sort valid applications
     */
    const sortedApplications = computed(() => {
        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;
        const requiredIds = requiredSubjects.map((s: any) => String(s.id));

        const scored: EnrolmentApplication[] = [];

        applications.value.forEach((app) => {
            const results: OLeveResult[] = app.academicResults || [];
            const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

            const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => a - b);
            const firstExamYear = uniqueYears[0] ?? 0;

            const mainScores = requiredIds.map((sid) => {
                const r = results.find((res) => String(res.subjectId) === sid);
                if (!r) return 9;
                return getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears);
            });

            const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));
            const sortedOthers = otherSubjects
                .sort(
                    (a, b) =>
                        getGradeScore(a.grade || 'N/A', a.examYear, firstExamYear, uniqueYears) -
                        getGradeScore(b.grade || 'N/A', b.examYear, firstExamYear, uniqueYears),
                )
                .slice(0, otherSubjectsCount);

            while (sortedOthers.length < otherSubjectsCount) {
                sortedOthers.push({ grade: 'N/A', examYear: firstExamYear } as OLeveResult);
            }

            const otherScores = sortedOthers.map((r) => getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears));

            const totalScore = [...mainScores, ...otherScores].reduce((sum, s) => sum + s, 0);
            const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);

            if (hasInvalidGrade || hasNoPayment) return;

            scored.push({
                ...app,
                totalScore,
                examSittingsCount: uniqueYears.length,
                firstExamYear,
            });
        });

        return scored.sort((a, b) => {
            if (a.totalScore !== b.totalScore) return a.totalScore - b.totalScore;
            return a.examSittingsCount - b.examSittingsCount;
        });
    });

    /**
     * Sync top-ranked applications with selected list
     */
    watch(
        [sortedApplications, classSize],
        ([sorted, size]) => {
            if (!sorted || !size) return;
            const newSelections: Record<string, boolean> = {};
            sorted.slice(0, size).forEach((app) => {
                newSelections[app.applicationId] = true;
            });
        },
        { immediate: true },
    );

    onMounted(() => {
        const initialSelections: Record<string, boolean> = {};
        sortedApplications.value.slice(0, classSize.value).forEach((app) => {
            initialSelections[app.applicationId] = true;
        });
        classListStore.$patch({
            classList: {
                ...classListStore.classList,
                ...initialSelections,
            },
        });
    });

    return {
        levelRequirements,
        requirementSubjects,
        getMainSubjectGrade,
        getOtherSubjectGrades,
        sortedApplications,
        faultyApplications,
    };
}
