import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { errorAlert, successAlert } from '@/lib/alerts';
import {
    getClassListTypeFromRank,
    getClassListTypeRowClass,
    getRankBandClassList,
    isWithinSelectableBand,
} from '@/lib/enrolmentClassListPresentation';
import type { DepartmentLevel } from '@/types/department-meta-data';
import { ClassSizeSlot, EnrolmentApplication, EnrolmentGroup, EnrolmentGroupResponse, OLeveResult } from '@/types/enrolments';
import { router, useForm } from '@inertiajs/vue3';

export const useEnrolments = () => {
    const allocateClassSlots = (classSize: number, disabledCount: number, femaleCount: number, maleCount: number): ClassSizeSlot => {
        // Step 1: Assign disabled share
        const remainingSlots = Math.max(classSize - disabledCount, 0);

        // Step 2: Split equally
        let femaleSlots = Math.floor(remainingSlots / 2);
        let maleSlots = Math.floor(remainingSlots / 2);
        const remainder = remainingSlots % 2;

        // Step 3: Handle odd remainder
        if (remainder === 1) {
            if (femaleCount > maleCount) {
                femaleSlots += 1;
            } else {
                maleSlots += 1;
            }
        }

        // Step 4: Adjust for population limits
        if (femaleCount < femaleSlots) {
            const transfer = femaleSlots - femaleCount;
            femaleSlots = femaleCount;
            maleSlots += transfer;
        }

        if (maleCount < maleSlots) {
            const transfer = maleSlots - maleCount;
            maleSlots = maleCount;
            femaleSlots += transfer;
        }

        // Ensure total doesn’t exceed class size
        const total = disabledCount + femaleSlots + maleSlots;
        if (total > classSize) {
            const excess = total - classSize;
            if (femaleSlots > maleSlots) {
                femaleSlots -= excess;
            } else {
                maleSlots -= excess;
            }
        }

        return {
            disabled: disabledCount,
            females: femaleSlots,
            males: maleSlots,
        };
    };

    /**
     * Converts a grade to a numeric score (lower = better)
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
     * Class-list selection ranking (frontend; backend sort deferred).
     *
     * Slot allocation (`allocateClassSlots`):
     * - Disability applicants take seats first (all of them).
     * - Remaining seats split 50/50 male/female; odd remainder goes to the larger pool.
     * - Unused seats from an under-populated gender transfer to the other.
     *
     * O-level ranking (`applyPolicyAlgorithmToApplications`):
     * - Score required subjects + top-N other subjects (lower is better).
     * - Drop invalid applications (any required score ≥ 9 / missing grades).
     * - Sort by totalScore → examSittingsCount → mainSubjectsScore → applicationDate.
     * - Callers must pass a level whose `relationships.requirement` is already
     *   the course row when one exists (`withRankingRequirement`).
     *
     * Known gaps for a future backend sort:
     * - Non-O-level tables stay name-ordered from the API.
     * - Disabled count can exceed class size (guidance, not a hard cap).
     * - Previously, gender "others" was unused in slot math (now removed).
     */
    function applyPolicyAlgorithmToApplications(
        applications: EnrolmentApplication[],
        level: DepartmentLevel | null | undefined,
    ): EnrolmentApplication[] {
        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;
        const requiredIds = requiredSubjects.map((s: any) => String(s.id));

        const scored: (EnrolmentApplication & { mainSubjectsScore: number })[] = [];

        applications.forEach((app) => {
            const results: OLeveResult[] = app.academicResults || [];
            const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

            const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => Number(a) - Number(b));
            const firstExamYear = uniqueYears[0] ?? 0;

            // Calculate scores for required subjects
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
            const mainSubjectsScore = mainScores.reduce((sum, s) => sum + s, 0);
            const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);

            if (hasInvalidGrade) return;
            //if (hasInvalidGrade || hasNoPayment ) return;

            scored.push({
                ...app,
                totalScore,
                examSittingsCount: uniqueYears.length,
                firstExamYear,
                mainSubjectsScore,
            });
        });

        // ✅ Sort by multiple criteria
        return scored.sort((a, b) => {
            if (a.totalScore !== b.totalScore) return a.totalScore - b.totalScore;
            if (a.examSittingsCount !== b.examSittingsCount) return a.examSittingsCount - b.examSittingsCount;
            if (a.mainSubjectsScore !== b.mainSubjectsScore) return a.mainSubjectsScore - b.mainSubjectsScore;
            return new Date(a.applicationDate).getTime() - new Date(b.applicationDate).getTime();
        });
    }

    /**
     * Identify applications that are faulty:
     * - No payment
     * - Missing required subject results
     * - Invalid or failing grades
     */
    function getFaultyApplications(applications: EnrolmentApplication[], level: DepartmentLevel | null | undefined): EnrolmentApplication[] {
        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;
        const requiredIds = requiredSubjects.map((s) => String(s.id));

        return applications.filter((app) => {
            const results: OLeveResult[] = app.academicResults || [];
            const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

            // No results = faulty automatically
            if (results.length === 0) return true;

            const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => Number(a) - Number(b));
            const firstExamYear = uniqueYears[0] ?? 0;

            // Required subjects
            const mainScores = requiredIds.map((sid) => {
                const r = results.find((res) => String(res.subjectId) === sid);
                if (!r) return 9; // missing required subject
                return getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears);
            });

            // Optional subjects
            const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));
            const sortedOthers = otherSubjects
                .sort(
                    (a, b) =>
                        getGradeScore(a.grade || 'N/A', a.examYear, firstExamYear, uniqueYears) -
                        getGradeScore(b.grade || 'N/A', b.examYear, firstExamYear, uniqueYears),
                )
                .slice(0, otherSubjectsCount);

            // Fill up if not enough optional subjects
            while (sortedOthers.length < otherSubjectsCount) {
                sortedOthers.push({ grade: 'N/A', examYear: firstExamYear } as OLeveResult);
            }

            const otherScores = sortedOthers.map((r) => getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears));

            // Faulty if any score is 9 (fail/missing) or no payment
            const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);
            return hasInvalidGrade;
            //return hasInvalidGrade || hasNoPayment;
        });
    }

    /**
     * Get main subject grade
     */
    const getMainSubjectGrade = (results: OLeveResult[], subjectId: string | number): OLeveResult | null | undefined => {
        return results.find((r) => String(r.subjectId) === String(subjectId));
    };

    /**
     * Get other subject grades (top N)
     */
    const getOtherSubjectGrades = (results: OLeveResult[], level: DepartmentLevel): Record<number, OLeveResult> => {
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

    const classListIsCreatedForGroup = (enrolments: EnrolmentGroupResponse, group: EnrolmentGroup): boolean => {
        const applications = enrolments?.groups?.[group] ?? [];

        return applications.some((enrolment) => enrolment.inClassList);
    };

    const classListIsCreated = (enrolments: EnrolmentGroupResponse) => {
        const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };

        return (['disabled', 'females', 'males'] as EnrolmentGroup[]).some((group) =>
            classListIsCreatedForGroup(enrolments, group),
        );
    };

    const addToClassList = async (studentApplicationId: string, type: string) => {
        const confirmed = await useCustomConfirmDialog().open({
            title: 'Create Class',
            message: `Are you sure you want to add application to ${type} list? `,
            confirmText: 'Please continue',
        });
        if (confirmed) {
            const form = useForm<{ type: string }>({
                type: type,
            });
            try {
                form.post(route('enrolments.add-to-class-list', { student_application: studentApplicationId }), {
                    onSuccess: () => {
                        successAlert('Application added to class list successfully');
                        router.visit(window.location.href, { replace: true, preserveScroll: true });
                    },
                    onError: (errors: Record<string, any>) => {
                        const message = Object.keys(errors).length ? Object.values(errors).join('\n') : 'Application could not added to class list';
                        errorAlert(message);
                    },
                });
            } catch {
                errorAlert('An unexpected error occurred while adding to class list');
            }
        }
    };

    const getRowClassList = (rowIndex: number, slotSize: number) => {
        const band = getRankBandClassList(rowIndex, slotSize);

        return band || 'j-tr';
    };

    const getClassListIconClass = (rowIndex: number, slotSize: number) => {
        if (rowIndex + 1 <= slotSize) {
            return 'text-green-600';
        }
        if (rowIndex + 1 > slotSize && rowIndex + 1 <= slotSize * 2) {
            return 'text-purple-600';
        }
        return '';
    };

    const getClassListType = (rowIndex: number, slotSize: number) => getClassListTypeFromRank(rowIndex, slotSize);

    const showAddToClassListBtn = (rowIndex: number, slotSize: number) => isWithinSelectableBand(rowIndex, slotSize);

    function groupByClassListType(applicants: EnrolmentApplication[]) {
        const order = ['provisional', 'waiting', 'failed', 'others', 'verified', 'final'];

        const groups = applicants.reduce((groups: Record<string, EnrolmentApplication[]>, applicant) => {
            const key = applicant.classListType && order.includes(applicant.classListType) ? applicant.classListType : 'others';

            if (!groups[key]) {
                groups[key] = [];
            }

            groups[key].push(applicant);
            return groups;
        }, {});

        // Return sorted object
        const sortedGroups: Record<string, EnrolmentApplication[]> = {};

        for (const key of order) {
            if (groups[key]) {
                sortedGroups[key] = groups[key];
            }
        }

        return sortedGroups;
    }
    const getClassListTypeClasses = (classListType: string) => {
        switch (classListType.toLowerCase()) {
            case 'final':
                return 'bg-emerald-100 text-emerald-800 border-emerald-800';
            case 'verified':
                return 'bg-primary/15 text-primary border-primary';
            case 'provisional':
                return 'bg-green-100 text-green-800 border-green-800';
            case 'waiting':
                return 'bg-purple-100 text-purple-800 border-purple-800';
            case 'failed':
                return 'bg-red-100 text-red-800 border-red-800';
            default:
                return 'bg-teal-100 text-teal-800 border-teal-800';
        }
    };

    const getClassListTypeDescription = (classListType: string) => {
        switch (classListType.toLowerCase()) {
            case 'final':
                return 'Applications have been enrolled';
            case 'verified':
                return 'Applications came for verification and details are verified';
            case 'provisional':
                return 'Applications have been added to the provisional class list and have not come for verification';
            case 'waiting':
                return 'Applications are in waiting list';
            case 'failed':
                return 'Applications are rejected probably the applicant opted for another course';
            default:
                return 'Applications did not qualify for class list provisional or waiting';
        }
    };

    return {
        allocateClassSlots,
        applyPolicyAlgorithmToApplications,
        getGradeScore,
        getFaultyApplications,
        getMainSubjectGrade,
        getOtherSubjectGrades,
        classListIsCreated,
        classListIsCreatedForGroup,
        addToClassList,
        getRowClassList,
        getClassListTypeRowClass,
        getClassListIconClass,
        getClassListType,
        showAddToClassListBtn,
        groupByClassListType,
        getClassListTypeClasses,
        getClassListTypeDescription,
    };
};
