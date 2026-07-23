import { BaseCheckbox } from '@/components/core/form';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, successAlert, warningDialog } from '@/lib/alerts';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import FaultyStudentIdCorrectionCell from '@/pages/maintenance/partials/students/FaultyStudentIdCorrectionCell.vue';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type {
    FaultyStudentIdConflict,
    FaultyStudentIdNumber,
    FaultyStudentIdsBulkFixResult,
    FaultyStudentIdsFiltersState,
    FaultyStudentRectificationStatus,
    FixStudentIdConflictResponse,
} from '@/types/faulty-student-ids';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';
import { h, ref } from 'vue';

interface CreateFaultyStudentIdColumnsOptions {
    draftIdNumbers: Ref<Record<number, string>>;
    savingStudentIds: Ref<Set<number>>;
    selectedStudentIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    onFixed: (studentIds: number[]) => void | Promise<void>;
}

interface FixStudentIdError {
    response?: {
        status?: number;
        data?: {
            message?: string;
            errors?: Record<string, string[]>;
            conflict?: FixStudentIdConflictResponse;
        };
    };
}

const toInertiaHref = (url: string): string => {
    if (!url) {
        return url;
    }

    try {
        if (url.startsWith('http://') || url.startsWith('https://')) {
            const parsed = new URL(url);

            if (parsed.origin === window.location.origin) {
                return `${parsed.pathname}${parsed.search}${parsed.hash}`;
            }
        }
    } catch {
        return url;
    }

    return url;
};

const statusLabel = (status: FaultyStudentRectificationStatus): string => {
    switch (status) {
        case 'duplicate_merge':
            return trans('trans.maintenance_faulty_data_status_duplicate');
        case 'ready_to_fix':
            return trans('trans.maintenance_faulty_data_status_ready');
        default:
            return trans('trans.maintenance_faulty_data_status_manual');
    }
};

const statusBadgeClass = (status: FaultyStudentRectificationStatus): string => {
    switch (status) {
        case 'duplicate_merge':
            return 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100';
        case 'ready_to_fix':
            return 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const buildMergeUrl = (
    student: FaultyStudentIdNumber,
    draftIdNumbers: Ref<Record<number, string>>,
    conflict?: FaultyStudentIdConflict | null,
): string | null => {
    const resolvedConflict = conflict ?? student.attributes.conflict;

    if (!resolvedConflict?.conflictingStudentId) {
        return null;
    }

    const idNumber = (draftIdNumbers.value[student.id] ?? resolvedConflict.idNumber ?? '').trim();

    if (!idNumber) {
        return null;
    }

    return toInertiaHref(
        route('maintenance.faulty-student-ids.merge', {
            student: student.id,
            target: resolvedConflict.conflictingStudentId,
            id_number: idNumber,
        }),
    );
};

const resolveMergeUrl = (
    conflict: FixStudentIdConflictResponse | undefined,
    studentId: number,
    draftIdNumber?: string,
): string | null => {
    if (!conflict) {
        return null;
    }

    const mergeUrl = conflict.mergeUrl ?? conflict.merge_url;
    const target = conflict.conflictingStudentId ?? conflict.conflicting_student_id;
    const idNumber = (draftIdNumber ?? conflict.idNumber ?? conflict.id_number ?? '').trim();

    if (mergeUrl) {
        return toInertiaHref(mergeUrl);
    }

    if (target && idNumber) {
        return toInertiaHref(
            route('maintenance.faulty-student-ids.merge', {
                student: studentId,
                target,
                id_number: idNumber,
            }),
        );
    }

    return null;
};

const isReadyToFix = (student: FaultyStudentIdNumber): boolean =>
    student.attributes.rectificationStatus === 'ready_to_fix';

export const useFaultyStudentIds = () => {
    const { textLink } = useDataTables();
    const { formatZimIdNumber } = useUtils();
    const isLoading = ref(false);
    const isBulkFixing = ref(false);

    const visitMergePreview = (url: string): void => {
        router.visit(url, {
            preserveState: false,
            onError: () => {
                errorAlert(trans('trans.maintenance_faulty_data_merge_failure'));
            },
        });
    };

    const fixStudentIdNumber = async (studentId: number, idNumber: string): Promise<void> => {
        await HttpService.patch(route('maintenance.faulty-student-ids.fix', studentId), {
            id_number: idNumber,
        });
    };

    const bulkFixStudentIdNumbers = async (studentIds: number[]): Promise<FaultyStudentIdsBulkFixResult> => {
        return await HttpService.post(route('maintenance.faulty-student-ids.bulk-fix'), {
            student_ids: studentIds,
        });
    };

    const handleSave = (
        student: FaultyStudentIdNumber,
        draftIdNumbers: Ref<Record<number, string>>,
        savingStudentIds: Ref<Set<number>>,
        onFixed: (studentIds: number[]) => void | Promise<void>,
    ) => {
        const studentId = student.id;
        const currentDraft = (draftIdNumbers.value[studentId] ?? '').trim();
        const originalId = student.attributes.idNumber.trim();
        const isSaving = savingStudentIds.value.has(studentId);
        const isUnchanged = currentDraft === originalId;
        const isValid = isValidZimbabweanIdNumber(currentDraft);

        if (isSaving || isUnchanged) {
            return;
        }

        if (!isValid) {
            errorAlert(trans('trans.enrollment_invalid_national_id'));
            return;
        }

        savingStudentIds.value = new Set([...savingStudentIds.value, studentId]);
        void fixStudentIdNumber(studentId, currentDraft)
            .then(async () => {
                successAlert(trans('trans.maintenance_faulty_data_fix_success'));
                await onFixed([studentId]);
            })
            .catch((error: FixStudentIdError) => {
                const responseData = error?.response?.data;
                const conflict = responseData?.conflict;
                const mergeUrl = resolveMergeUrl(conflict, studentId, currentDraft);

                if (error?.response?.status == 409 && mergeUrl) {
                    visitMergePreview(mergeUrl);
                    return;
                }

                const messages = responseData?.errors?.id_number;
                errorAlert(messages?.[0] ?? trans('trans.maintenance_faulty_data_fix_failure'));
            })
            .finally(() => {
                const next = new Set(savingStudentIds.value);
                next.delete(studentId);
                savingStudentIds.value = next;
            });
    };

    const handleBulkFix = (
        studentIds: number[],
        onFixed: (studentIds: number[]) => void | Promise<void>,
    ): void => {
        if (studentIds.length === 0 || isBulkFixing.value) {
            return;
        }

        warningDialog(
            async () => {
                isBulkFixing.value = true;

                try {
                    const result = await bulkFixStudentIdNumbers(studentIds);
                    const fixedCount = result.fixed_ids.length;
                    const failedCount = result.failed.length;

                    if (fixedCount > 0) {
                        if (failedCount > 0) {
                            successAlert(
                                trans('trans.maintenance_faulty_data_bulk_fix_partial', {
                                    fixed: String(fixedCount),
                                    failed: String(failedCount),
                                }),
                            );
                        } else {
                            successAlert(
                                trans('trans.maintenance_faulty_data_bulk_fix_success', {
                                    count: String(fixedCount),
                                }),
                            );
                        }

                        await onFixed(result.fixed_ids);
                    } else {
                        const firstFailure = result.failed[0]?.message;
                        errorAlert(firstFailure ?? trans('trans.maintenance_faulty_data_bulk_fix_failure'));
                    }
                } catch {
                    errorAlert(trans('trans.maintenance_faulty_data_bulk_fix_failure'));
                } finally {
                    isBulkFixing.value = false;
                }

                return true;
            },
            trans('trans.maintenance_faulty_data_bulk_fix_confirm', {
                count: String(studentIds.length),
            }),
            trans('trans.warning'),
            trans('trans.maintenance_faulty_data_bulk_fix', {
                count: String(studentIds.length),
            }),
        );
    };

    const createFaultyStudentIdColumns = ({
        draftIdNumbers,
        savingStudentIds,
        selectedStudentIds,
        selectAllModel,
        onFixed,
    }: CreateFaultyStudentIdColumnsOptions) => [
        {
            header: () =>
                h(BaseCheckbox, {
                    inputId: 'select_all_faulty_student_ids',
                    label: '',
                    modelValue: selectAllModel.value,
                    'onUpdate:modelValue': (value: boolean) => {
                        selectAllModel.value = value;
                    },
                }),
            accessorKey: 'select',
            enableSorting: false,
            meta: { align: 'center' },
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) => {
                const student = row.original;
                const selectable = isReadyToFix(student);

                return h(BaseCheckbox, {
                    inputId: `select_faulty_student_${student.id}`,
                    label: '',
                    modelValue: selectedStudentIds.value,
                    'onUpdate:modelValue': (value: number[]) => {
                        selectedStudentIds.value = value;
                    },
                    value: student.id,
                    disabled: !selectable,
                });
            },
        },
        {
            header: trans_choice('trans.name', 1),
            accessorKey: 'name',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                textLink(
                    buildStudentShowUrl(row.original.id, {
                        from: 'maintenance',
                        return: route('maintenance.faulty-student-ids'),
                    }),
                    row.original.attributes.name ?? '---',
                ),
        },
        {
            header: trans('trans.phone_number'),
            accessorKey: 'attributes.phoneNumber',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                h('span', { class: 'text-sm whitespace-nowrap' }, row.original.attributes.phoneNumber ?? '---'),
        },
        {
            header: trans('trans.email_address'),
            accessorKey: 'attributes.email',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                h('span', { class: 'text-sm truncate max-w-[180px] block' }, row.original.attributes.email ?? '---'),
        },
        {
            header: trans_choice('trans.student_number', 1),
            accessorKey: 'attributes.studentNumber',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                h('span', { class: 'text-sm font-mono' }, row.original.attributes.studentNumber ?? '---'),
        },
        {
            header: trans_choice('trans.status', 1),
            accessorKey: 'attributes.rectificationStatus',
            enableSorting: false,
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) => {
                const status = row.original.attributes.rectificationStatus;

                return h(
                    'span',
                    {
                        class: `inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeClass(status)}`,
                    },
                    statusLabel(status),
                );
            },
        },
        {
            header: trans('trans.maintenance_faulty_data_current_id'),
            accessorKey: 'attributes.idNumber',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                h('span', { class: 'font-mono text-xs text-destructive whitespace-nowrap' }, row.original.attributes.idNumber),
        },
        {
            header: trans('trans.maintenance_faulty_data_new_id'),
            accessorKey: 'newIdNumber',
            enableSorting: false,
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) => {
                const student = row.original;
                const studentId = student.id;
                const currentDraft = draftIdNumbers.value[studentId] ?? '';
                const originalId = student.attributes.idNumber;
                const isUnchanged = currentDraft.trim() === originalId.trim();
                const isValid = isValidZimbabweanIdNumber(currentDraft);
                const suggested = student.attributes.suggestedIdNumber;
                const isDuplicateMerge = student.attributes.rectificationStatus === 'duplicate_merge';
                const isSaving = savingStudentIds.value.has(studentId);

                return h(FaultyStudentIdCorrectionCell, {
                    modelValue: currentDraft,
                    suggestedIdNumber: suggested,
                    inputId: `faulty_id_number_correction_${studentId}`,
                    processing: isSaving,
                    canSave: !isUnchanged && isValid,
                    showSaveButton: !isDuplicateMerge,
                    'onUpdate:modelValue': (value: string) => {
                        draftIdNumbers.value[studentId] = formatZimIdNumber(value) ?? value;
                    },
                    onUseSuggested: () => {
                        if (suggested) {
                            draftIdNumbers.value[studentId] = suggested;
                        }
                    },
                    onSave: () => handleSave(student, draftIdNumbers, savingStudentIds, onFixed),
                });
            },
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) => {
                const student = row.original;
                const mergeUrl = buildMergeUrl(student, draftIdNumbers);

                if (student.attributes.rectificationStatus !== 'duplicate_merge' || !mergeUrl) {
                    return h('span', { class: 'text-muted-foreground text-xs' }, '---');
                }

                return textLink(mergeUrl, trans('trans.maintenance_faulty_data_compare_merge'));
            },
        },
    ];

    const fetchFaultyStudentIds = async (
        filters: FaultyStudentIdsFiltersState = {},
        paginatorUrl?: string,
    ): Promise<ApiFilterResponse | undefined> => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('maintenance.faulty-student-ids.data');
            const url = new URL(baseUrl, window.location.origin);

            if (filters.search) {
                url.searchParams.set('search', filters.search);
            }

            return await HttpService.get(url.pathname + url.search);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.maintenance_faulty_data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const syncDraftIdNumbers = (
        students: FaultyStudentIdNumber[],
        draftIdNumbers: Ref<Record<number, string>>,
    ): void => {
        const nextDrafts: Record<number, string> = { ...draftIdNumbers.value };

        for (const student of students) {
            if (!(student.id in nextDrafts)) {
                nextDrafts[student.id] = student.attributes.proposedIdNumber ?? student.attributes.idNumber;
            }
        }

        draftIdNumbers.value = nextDrafts;
    };

    return {
        createFaultyStudentIdColumns,
        fetchFaultyStudentIds,
        syncDraftIdNumbers,
        handleBulkFix,
        isLoading,
        isBulkFixing,
    };
};
