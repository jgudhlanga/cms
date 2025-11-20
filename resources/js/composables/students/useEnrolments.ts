import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, successAlert } from '@/lib/alerts';
import { mergeValidationSchema } from '@/lib/forms';
import { emailUniqueSchema, idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import type { DepartmentLevel } from '@/types/department-meta-data';
import { ClassSizeSlot, Enrolment, EnrolmentApplication, EnrolmentGroup, EnrolmentGroupResponse, OLeveResult } from '@/types/enrolments';
import { InertiaForm, router, useForm } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';

export const useEnrolments = () => {
    const { moreActionButton, textLink } = useDataTables();

    const enrolmentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const studentName = row.original?.attributes?.studentName;
                    const studentId = String(row.original?.attributes?.studentId);
                    return textLink(route('enrolments.show-profile', { student: studentId }), studentName ?? '');
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return moreActionButton(false, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const cashApplicationFormSchema = (isNativeCitizen: boolean) => {
        const personal = [
            'firstNameSchema',
            'lastNameSchema',
            'genderSchema',
            'maritalStatusSchema',
            'dobSchema',
            'idTypeSchema',
            'addressOneSchema',
            'addressTwoSchema',
            'addressThreeSchema',
            'emailSchema',
            'phoneNumberSchema',
            'nextOfKinPhoneNumberSchema',
            'nextOfKinAddressOneSchema',
            'nextOfKinAddressTwoSchema',
            'nextOfKinAddressThreeSchema',
            'relationshipSchema',
            'nextOfKinNameSchema',
            'levelSchema',
            'courseSchema',
            'departmentSchema',
            'modeOfStudySchema',
            'paymentReferenceSchema',
            'paymentDateSchema',
            'proofOfPaymentSchema',
        ];
        let personalDetails = null;
        if (isNativeCitizen) {
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(idNumberUniqueSchema('api/v1/validations/check?key=student_national_id&value='))
                    .merge(emailUniqueSchema('api/v1/validations/check?key=user_email&value=')),
            );
        } else {
            personal.push('countrySchema');
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(passportNumberUniqueSchema('api/v1/validations/check?key=student_passport_number&value='))
                    .merge(emailUniqueSchema('api/v1/validations/check?key=user_email&value=')),
            );
        }
        return personalDetails;
    };

    const createEnrolment = (form: InertiaForm<any>) => {
        try {
            form.post(route('students.store'), {
                onSuccess: () => {
                    const store = useCreateApplicationFormStore();
                    store.$reset();
                    store.$dispose();
                    successAlert('Application successfully created');
                },
                onError: (errors: any) => {
                    if (Object.keys(errors).length) {
                        const allErrors = Object.values(errors).join('\n');
                        errorAlert(allErrors);
                    } else {
                        errorAlert('An unexpected error happened, application could not be created');
                    }
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

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

            if (hasInvalidGrade || hasNoPayment) return;

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
            return hasInvalidGrade || hasNoPayment;
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

    const classListIsCreated = (enrolments: EnrolmentGroupResponse) => {
        const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };
        return ['disabled', 'females', 'males'].some((group) => groups[group as EnrolmentGroup].some((enrolment) => enrolment.inClassList));
    };

    const addToClassList = async (studentProgramId: string, type: string) => {
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
                form.post(route('enrolments.add-to-class-list', {student_program: studentProgramId}), {
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
        if (rowIndex + 1 <= slotSize) {
            return 'bg-green-100';
        }
        if (rowIndex + 1 > slotSize && rowIndex + 1 <= slotSize * 2) {
            return 'bg-purple-100';
        }
        return 'j-tr';
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

    const getClassListType = (rowIndex: number, slotSize: number) => {
        if (rowIndex + 1 <= slotSize) {
            return 'provisional';
        }
        if (rowIndex + 1 > slotSize && rowIndex + 1 <= slotSize * 2) {
            return 'waiting';
        }
        return '';
    };
    const showAddToClassListBtn = (rowIndex: number, slotSize: number) => {
        return rowIndex + 1 <= slotSize * 2;
    };

    return {
        enrolmentColumns,
        cashApplicationFormSchema,
        createEnrolment,
        allocateClassSlots,
        applyPolicyAlgorithmToApplications,
        getGradeScore,
        getFaultyApplications,
        getMainSubjectGrade,
        getOtherSubjectGrades,
        classListIsCreated,
        addToClassList,
        getRowClassList,
        getClassListIconClass,
        getClassListType,
        showAddToClassListBtn,
    };
};
