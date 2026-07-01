import { useDataTables } from '@/composables/core/useDataTables';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import type {
    BulkFinaliseDispatchResponse,
    BulkFinaliseRunProgress,
    VerifiedStudentForFinalEnrolment,
    VerifiedStudentPaymentEligibility,
    VerifiedStudentsFinalEnrolmentApiResponse,
    VerifiedStudentsFinalEnrolmentFiltersState,
} from '@/types/verified-students-final-enrolment';
import { trans } from 'laravel-vue-i18n';
import { h, ref } from 'vue';

const eligibilityLabel = (eligibility: VerifiedStudentPaymentEligibility): string => {
    switch (eligibility) {
        case 'eligible':
            return trans('trans.maintenance_verified_students_final_enrolment_eligible');
        case 'missing_student_number':
            return trans('trans.maintenance_verified_students_final_enrolment_missing_student_number');
        default:
            return trans('trans.maintenance_verified_students_final_enrolment_no_payment');
    }
};

const eligibilityBadgeClass = (eligibility: VerifiedStudentPaymentEligibility): string => {
    switch (eligibility) {
        case 'eligible':
            return 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
        case 'missing_student_number':
            return 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100';
        default:
            return 'bg-destructive/15 text-destructive';
    }
};

const rowHighlightClass = (eligibility: VerifiedStudentPaymentEligibility): string => {
    if (eligibility === 'eligible') {
        return '';
    }

    return eligibility === 'missing_student_number'
        ? 'bg-amber-50/80 dark:bg-amber-950/20'
        : 'bg-destructive/5';
};

export const useVerifiedStudentsFinalEnrolment = () => {
    const { textLink } = useDataTables();
    const isLoading = ref(false);
    const isSummaryLoading = ref(false);
    const isRunning = ref(false);
    const runProgress = ref<BulkFinaliseRunProgress | null>(null);
    const activeRunId = ref<string | null>(null);

    const createVerifiedStudentColumns = () => [
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_name'),
            accessorKey: 'name',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) => {
                const student = row.original;
                const highlightClass = rowHighlightClass(student.attributes.paymentEligibility);

                return h(
                    'div',
                    { class: highlightClass },
                    student.attributes.studentId
                        ? textLink(
                              buildStudentShowUrl(student.attributes.studentId, {
                                  from: 'maintenance',
                                  return: route('maintenance.verified-students-final-enrolment'),
                              }),
                              student.attributes.name ?? '---',
                          )
                        : h('span', student.attributes.name ?? '---'),
                );
            },
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_student_number'),
            accessorKey: 'attributes.studentNumber',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) =>
                h(
                    'span',
                    {
                        class: `text-sm font-mono ${rowHighlightClass(row.original.attributes.paymentEligibility)}`,
                    },
                    row.original.attributes.studentNumber ?? '---',
                ),
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_id_number'),
            accessorKey: 'attributes.idNumber',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) =>
                h(
                    'span',
                    {
                        class: `text-sm font-mono ${rowHighlightClass(row.original.attributes.paymentEligibility)}`,
                    },
                    row.original.attributes.idNumber ?? '---',
                ),
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_department'),
            accessorKey: 'attributes.department',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) =>
                h('span', { class: 'text-sm' }, row.original.attributes.department ?? '---'),
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_level'),
            accessorKey: 'attributes.level',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) =>
                h('span', { class: 'text-sm' }, row.original.attributes.level ?? '---'),
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_course'),
            accessorKey: 'attributes.course',
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) =>
                h('span', { class: 'text-sm' }, row.original.attributes.course ?? '---'),
        },
        {
            header: trans('trans.maintenance_verified_students_final_enrolment_column_payment_status'),
            accessorKey: 'attributes.paymentEligibility',
            enableSorting: false,
            cell: ({ row }: { row: { original: VerifiedStudentForFinalEnrolment } }) => {
                const eligibility = row.original.attributes.paymentEligibility;

                return h(
                    'span',
                    {
                        class: `inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${eligibilityBadgeClass(eligibility)}`,
                    },
                    eligibilityLabel(eligibility),
                );
            },
        },
    ];

    const fetchVerifiedStudents = async (
        filters: VerifiedStudentsFinalEnrolmentFiltersState = {},
        paginatorUrl?: string,
    ): Promise<VerifiedStudentsFinalEnrolmentApiResponse | undefined> => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('maintenance.verified-students-final-enrolment.data');
            const path = mergeQueryParamsIntoRequestPath(baseUrl, filters as Record<string, unknown>);

            return (await HttpService.get(path)) as VerifiedStudentsFinalEnrolmentApiResponse;
        } catch {
            errorAlert(
                trans('trans.load_data_failure', {
                    data: trans('trans.maintenance_verified_students_final_enrolment'),
                }),
            );
        } finally {
            isLoading.value = false;
        }
    };

    const fetchPaymentSummary = async (
        filters: VerifiedStudentsFinalEnrolmentFiltersState = {},
    ): Promise<VerifiedStudentsFinalEnrolmentApiResponse | undefined> => {
        try {
            isSummaryLoading.value = true;
            const path = mergeQueryParamsIntoRequestPath(
                route('maintenance.verified-students-final-enrolment.summary'),
                filters as Record<string, unknown>,
            );

            return (await HttpService.get(path)) as VerifiedStudentsFinalEnrolmentApiResponse;
        } catch {
            errorAlert(
                trans('trans.load_data_failure', {
                    data: trans('trans.maintenance_verified_students_final_enrolment'),
                }),
            );
        } finally {
            isSummaryLoading.value = false;
        }
    };

    const pollRunStatus = async (runId: string): Promise<BulkFinaliseRunProgress | undefined> => {
        try {
            return (await HttpService.get(
                route('maintenance.verified-students-final-enrolment.run-status', runId),
            )) as BulkFinaliseRunProgress;
        } catch {
            return undefined;
        }
    };

    const dispatchBulkFinalise = async (): Promise<BulkFinaliseDispatchResponse | undefined> => {
        try {
            return (await HttpService.post(route('maintenance.verified-students-final-enrolment.run'))) as BulkFinaliseDispatchResponse;
        } catch (error: unknown) {
            const response = error as { response?: { data?: { message?: string }; status?: number } };
            const message =
                response?.response?.data?.message ??
                trans('trans.maintenance_verified_students_final_enrolment_run_failed', { message: 'Unknown error' });

            errorAlert(message);

            return undefined;
        }
    };

    const waitForRunCompletion = (
        runId: string,
        onProgress: (progress: BulkFinaliseRunProgress) => void,
    ): Promise<BulkFinaliseRunProgress> =>
        new Promise((resolve, reject) => {
            const poll = async (): Promise<void> => {
                const progress = await pollRunStatus(runId);

                if (!progress) {
                    reject(new Error('Unable to fetch bulk finalise progress.'));

                    return;
                }

                onProgress(progress);

                if (progress.status === 'completed') {
                    resolve(progress);

                    return;
                }

                if (progress.status === 'failed') {
                    reject(new Error(progress.message ?? 'Bulk finalise failed.'));

                    return;
                }

                window.setTimeout(() => {
                    void poll();
                }, 2000);
            };

            void poll();
        });

    const confirmAndRunBulkFinalise = async (onComplete: () => void | Promise<void>): Promise<void> => {
        if (isRunning.value) {
            return;
        }

        warningDialog(
            async () => {
                isRunning.value = true;
                runProgress.value = null;

                const dispatch = await dispatchBulkFinalise();

                if (!dispatch) {
                    isRunning.value = false;

                    return false;
                }

                successAlert(dispatch.message);
                activeRunId.value = dispatch.runId;

                try {
                    const finalProgress = await waitForRunCompletion(dispatch.runId, (progress) => {
                        runProgress.value = progress;
                    });

                    successAlert(
                        trans('trans.maintenance_verified_students_final_enrolment_run_completed', {
                            successful: String(finalProgress.successful),
                            failed: String(finalProgress.failed),
                        }),
                    );

                    await onComplete();
                } catch (error: unknown) {
                    const message = error instanceof Error ? error.message : 'Unknown error';
                    errorAlert(
                        trans('trans.maintenance_verified_students_final_enrolment_run_failed', {
                            message,
                        }),
                    );
                } finally {
                    isRunning.value = false;
                    activeRunId.value = null;
                    runProgress.value = null;
                }

                return true;
            },
            trans('trans.maintenance_verified_students_final_enrolment_run_confirm'),
            trans('trans.warning'),
            trans('trans.maintenance_verified_students_final_enrolment_run'),
        );
    };

    const runButtonLabel = (): string => {
        if (!isRunning.value || !runProgress.value) {
            return trans('trans.maintenance_verified_students_final_enrolment_run');
        }

        return trans('trans.maintenance_verified_students_final_enrolment_run_processing', {
            processed: String(runProgress.value.processed),
            total: String(runProgress.value.total),
        });
    };

    return {
        createVerifiedStudentColumns,
        fetchVerifiedStudents,
        fetchPaymentSummary,
        confirmAndRunBulkFinalise,
        isLoading,
        isSummaryLoading,
        isRunning,
        runProgress,
        runButtonLabel,
    };
};
