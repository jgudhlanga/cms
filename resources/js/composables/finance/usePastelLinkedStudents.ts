import { BaseCheckbox } from '@/components/core/form';
import { useDataTables } from '@/composables/core/useDataTables';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { successAlert } from '@/lib/alerts';
import type { PastelLinkedStudent } from '@/types/finance';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';
import { computed, h, ref, watch } from 'vue';

const resolveLinkedStudentId = (linkedStudent: PastelLinkedStudent): number => {
    return Number(linkedStudent.id);
};

export function usePastelLinkedStudents(linkedStudents: ComputedRef<PastelLinkedStudent[]>) {
    const { actionButton } = useDataTables();
    const { formatDate } = useUtils();
    const { open: openConfirmDialog } = useCustomConfirmDialog();

    const selectedLinkedStudentIds: Ref<number[]> = ref([]);

    const selectAllModel = computed({
        get() {
            const list = linkedStudents.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedLinkedStudentIds.value);

            return list.every((student) => selectedSet.has(resolveLinkedStudentId(student)));
        },
        set(checked: boolean) {
            if (checked) {
                selectedLinkedStudentIds.value = linkedStudents.value.map(resolveLinkedStudentId);
            } else {
                selectedLinkedStudentIds.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedLinkedStudentIds.value.length);

    const clearSelection = (): void => {
        selectedLinkedStudentIds.value = [];
    };

    const pruneSelectionToVisibleStudents = (): void => {
        const visibleIds = new Set(linkedStudents.value.map(resolveLinkedStudentId));
        selectedLinkedStudentIds.value = selectedLinkedStudentIds.value.filter((id) => visibleIds.has(id));
    };

    watch(linkedStudents, () => {
        pruneSelectionToVisibleStudents();
    });

    const unlinkStudent = async (linkedStudent: PastelLinkedStudent): Promise<void> => {
        const confirmed = await openConfirmDialog({
            title: trans('finance.pastel_export_unlink_confirm_title'),
            message: trans('finance.pastel_export_unlink_confirm_message'),
            note: trans('finance.pastel_export_unlink_confirm_note'),
            confirmText: trans('finance.pastel_export_unlink'),
            cancelText: trans('trans.cancel'),
        });

        if (!confirmed) {
            return;
        }

        router.delete(route('finance.pastel-export.linked-students.destroy', linkedStudent.id), {
            preserveScroll: true,
            onSuccess: () => {
                selectedLinkedStudentIds.value = selectedLinkedStudentIds.value.filter(
                    (id) => id !== resolveLinkedStudentId(linkedStudent),
                );
                successAlert(trans('finance.pastel_export_unlink_success'));
            },
        });
    };

    const unlinkSelectedStudents = async (): Promise<void> => {
        const ids = [...selectedLinkedStudentIds.value];

        if (ids.length === 0) {
            return;
        }

        const confirmed = await openConfirmDialog({
            title: trans('finance.pastel_export_bulk_unlink_confirm_title'),
            message: trans('finance.pastel_export_bulk_unlink_confirm_message', { count: ids.length }),
            note: trans('finance.pastel_export_unlink_confirm_note'),
            confirmText: trans('finance.pastel_export_bulk_unlink'),
            cancelText: trans('trans.cancel'),
        });

        if (!confirmed) {
            return;
        }

        router.delete(route('finance.pastel-export.linked-students.bulk-destroy'), {
            data: { ids },
            preserveScroll: true,
            onSuccess: () => {
                clearSelection();
                successAlert(trans('finance.pastel_export_bulk_unlink_success', { count: ids.length }));
            },
        });
    };

    const createLinkedStudentColumns = () => [
        {
            header: () =>
                h(BaseCheckbox, {
                    inputId: 'select_all_pastel_linked_students',
                    label: '',
                    modelValue: selectAllModel.value,
                    'onUpdate:modelValue': (value: boolean) => {
                        selectAllModel.value = value;
                    },
                }),
            accessorKey: 'select',
            enableSorting: false,
            meta: { align: 'center' },
            cell: ({ row }: { row: { original: PastelLinkedStudent } }) => {
                const id = resolveLinkedStudentId(row.original);

                return h(BaseCheckbox, {
                    inputId: `select_pastel_linked_student_${id}`,
                    label: '',
                    modelValue: selectedLinkedStudentIds.value.includes(id),
                    'onUpdate:modelValue': (checked: boolean) => {
                        if (checked) {
                            if (!selectedLinkedStudentIds.value.includes(id)) {
                                selectedLinkedStudentIds.value = [...selectedLinkedStudentIds.value, id];
                            }
                        } else {
                            selectedLinkedStudentIds.value = selectedLinkedStudentIds.value.filter(
                                (selectedId) => selectedId !== id,
                            );
                        }
                    },
                });
            },
        },
        {
            header: trans('finance.pastel_export_student_number'),
            accessorKey: 'attributes.studentNumber',
        },
        {
            header: trans('finance.pastel_export_student_name'),
            accessorKey: 'attributes.studentName',
        },
        {
            header: trans('finance.pastel_export_linked_at'),
            accessorKey: 'attributes.linkedAt',
            cell: ({ row }: { row: { original: PastelLinkedStudent } }) => {
                const linkedAt = row.original.attributes.linkedAt;

                return linkedAt ? formatDate(linkedAt, 'LLL') : '—';
            },
        },
        {
            header: trans('finance.pastel_export_linked_by'),
            accessorKey: 'attributes.linkedByName',
            cell: ({ row }: { row: { original: PastelLinkedStudent } }) => {
                return row.original.attributes.linkedByName || '—';
            },
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }: { row: { original: PastelLinkedStudent } }) => {
                return actionButton({
                    title: trans('finance.pastel_export_unlink'),
                    variant: ColorVariant.danger_outline,
                    onClick: () => {
                        void unlinkStudent(row.original);
                    },
                });
            },
        },
    ];

    return {
        createLinkedStudentColumns,
        unlinkStudent,
        unlinkSelectedStudents,
        selectedLinkedStudentIds,
        selectAllModel,
        selectedCount,
        clearSelection,
    };
}
