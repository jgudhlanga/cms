import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, successAlert } from '@/lib/alerts';
import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import FaultyStudentIdCorrectionCell from '@/pages/maintenance/partials/students/FaultyStudentIdCorrectionCell.vue';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type { FaultyStudentIdNumber, FaultyStudentIdsFiltersState } from '@/types/faulty-student-ids';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { Ref } from 'vue';
import { h, ref } from 'vue';

interface CreateFaultyStudentIdColumnsOptions {
    draftIdNumbers: Ref<Record<number, string>>;
    savingStudentIds: Ref<Set<number>>;
    onSaveSuccess: () => void | Promise<void>;
}

interface FixStudentIdError {
    response?: {
        status?: number;
        data?: {
            errors?: Record<string, string[]>;
            conflict?: {
                conflictingStudentId: number;
                idNumber: string;
            };
        };
    };
}

export const useFaultyStudentIds = () => {
    const { textLink } = useDataTables();
    const { formatZimIdNumber, navigateTo } = useUtils();
    const isLoading = ref(false);

    const fixStudentIdNumber = async (studentId: number, idNumber: string): Promise<void> => {
        await HttpService.patch(route('maintenance.faulty-student-ids.fix', studentId), {
            id_number: idNumber,
        });
    };

    const handleSave = (
        student: FaultyStudentIdNumber,
        draftIdNumbers: Ref<Record<number, string>>,
        savingStudentIds: Ref<Set<number>>,
        onSaveSuccess: () => void | Promise<void>,
    ) => {
        const studentId = student.id;
        const currentDraft = draftIdNumbers.value[studentId] ?? '';
        const originalId = student.attributes.idNumber;
        const isSaving = savingStudentIds.value.has(studentId);
        const isUnchanged = currentDraft.trim() === originalId.trim();
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
                await onSaveSuccess();
            })
            .catch((error: FixStudentIdError) => {
                const conflict = error?.response?.data?.conflict;

                if (error?.response?.status === 409 && conflict) {
                    navigateTo(
                        route('maintenance.faulty-student-ids.merge', {
                            student: studentId,
                            target: conflict.conflictingStudentId,
                            id_number: conflict.idNumber,
                        }),
                    );
                    return;
                }

                const messages = error?.response?.data?.errors?.id_number;
                errorAlert(messages?.[0] ?? trans('trans.maintenance_faulty_data_fix_failure'));
            })
            .finally(() => {
                const next = new Set(savingStudentIds.value);
                next.delete(studentId);
                savingStudentIds.value = next;
            });
    };

    const createFaultyStudentIdColumns = ({
        draftIdNumbers,
        savingStudentIds,
        onSaveSuccess,
    }: CreateFaultyStudentIdColumnsOptions) => [
        {
            header: trans_choice('trans.name', 1),
            accessorKey: 'name',
            cell: ({ row }: { row: { original: FaultyStudentIdNumber } }) =>
                textLink(route('students.show', String(row.original.id)), row.original.attributes.name ?? '---'),
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

                return h(FaultyStudentIdCorrectionCell, {
                    currentId: originalId,
                    modelValue: currentDraft,
                    suggestedIdNumber: suggested,
                    disabled: savingStudentIds.value.has(studentId),
                    canSave: !isUnchanged && isValid,
                    'onUpdate:modelValue': (value: string) => {
                        draftIdNumbers.value[studentId] = formatZimIdNumber(value);
                    },
                    onUseSuggested: () => {
                        if (suggested) {
                            draftIdNumbers.value[studentId] = suggested;
                        }
                    },
                    onSave: () => handleSave(student, draftIdNumbers, savingStudentIds, onSaveSuccess),
                });
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
                nextDrafts[student.id] = student.attributes.idNumber;
            }
        }

        draftIdNumbers.value = nextDrafts;
    };

    return {
        createFaultyStudentIdColumns,
        fetchFaultyStudentIds,
        syncDraftIdNumbers,
        isLoading,
        navigateTo,
    };
};
