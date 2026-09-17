import { BaseCheckbox } from '@/components/core/form';
import { useDataTables } from '@/composables/core/useDataTables';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { successAlert } from '@/lib/alerts';
import type { StudentBillingRecord } from '@/types/finance';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';
import { computed, h, ref, watch } from 'vue';

const resolveRecordId = (record: StudentBillingRecord): number => Number(record.id);

const isExported = (record: StudentBillingRecord): boolean => record.attributes.status === 'exported';
const isBilled = (record: StudentBillingRecord): boolean => record.attributes.status === 'billed';

export function useBillingExportRecords(billingRecords: ComputedRef<StudentBillingRecord[]>) {
    const { actionButton } = useDataTables();
    const { formatDate } = useUtils();
    const { open: openConfirmDialog } = useCustomConfirmDialog();

    const selectedRecordIds: Ref<number[]> = ref([]);

    const selectAllModel = computed({
        get() {
            const list = billingRecords.value;
            if (list.length === 0) {
                return false;
            }

            const selectedSet = new Set(selectedRecordIds.value);

            return list.every((record) => selectedSet.has(resolveRecordId(record)));
        },
        set(checked: boolean) {
            if (checked) {
                selectedRecordIds.value = billingRecords.value.map(resolveRecordId);
            } else {
                selectedRecordIds.value = [];
            }
        },
    });

    const selectedCount = computed(() => selectedRecordIds.value.length);

    const selectedExportedIds = computed(() =>
        billingRecords.value
            .filter((record) => selectedRecordIds.value.includes(resolveRecordId(record)) && isExported(record))
            .map(resolveRecordId),
    );

    const selectedBilledIds = computed(() =>
        billingRecords.value
            .filter((record) => selectedRecordIds.value.includes(resolveRecordId(record)) && isBilled(record))
            .map(resolveRecordId),
    );

    const clearSelection = (): void => {
        selectedRecordIds.value = [];
    };

    const pruneSelectionToVisibleRecords = (): void => {
        const visibleIds = new Set(billingRecords.value.map(resolveRecordId));
        selectedRecordIds.value = selectedRecordIds.value.filter((id) => visibleIds.has(id));
    };

    watch(billingRecords, () => {
        pruneSelectionToVisibleRecords();
    });

    const postSelected = (url: string, ids: number[], successKey: string): void => {
        router.post(
            url,
            { ids },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clearSelection();
                    successAlert(trans(successKey, { count: ids.length }));
                },
            },
        );
    };

    const markBilled = async (ids: number[]): Promise<void> => {
        if (ids.length === 0) {
            return;
        }

        const confirmed = await openConfirmDialog({
            title: trans('finance.billing_export_mark_billed_confirm_title'),
            message: trans('finance.billing_export_mark_billed_confirm_message', { count: ids.length }),
            confirmText: trans('finance.billing_export_mark_billed'),
            cancelText: trans('trans.cancel'),
        });

        if (!confirmed) {
            return;
        }

        postSelected(route('finance.billing-export.mark-billed'), ids, 'finance.billing_export_mark_billed_success');
    };

    const markFailed = async (ids: number[]): Promise<void> => {
        if (ids.length === 0) {
            return;
        }

        const confirmed = await openConfirmDialog({
            title: trans('finance.billing_export_mark_failed_confirm_title'),
            message: trans('finance.billing_export_mark_failed_confirm_message', { count: ids.length }),
            confirmText: trans('finance.billing_export_mark_failed'),
            cancelText: trans('trans.cancel'),
        });

        if (!confirmed) {
            return;
        }

        postSelected(route('finance.billing-export.mark-failed'), ids, 'finance.billing_export_mark_failed_success');
    };

    const unbill = async (ids: number[], bulk: boolean): Promise<void> => {
        if (ids.length === 0) {
            return;
        }

        const confirmed = await openConfirmDialog({
            title: trans(
                bulk
                    ? 'finance.billing_export_bulk_unbill_confirm_title'
                    : 'finance.billing_export_unbill_confirm_title',
            ),
            message: trans(
                bulk
                    ? 'finance.billing_export_bulk_unbill_confirm_message'
                    : 'finance.billing_export_unbill_confirm_message',
                { count: ids.length },
            ),
            note: trans('finance.billing_export_unbill_confirm_note'),
            confirmText: trans(bulk ? 'finance.billing_export_bulk_unbill' : 'finance.billing_export_unbill'),
            cancelText: trans('trans.cancel'),
        });

        if (!confirmed) {
            return;
        }

        if (bulk) {
            router.delete(route('finance.billing-export.records.bulk-destroy'), {
                data: { ids },
                preserveScroll: true,
                onSuccess: () => {
                    clearSelection();
                    successAlert(trans('finance.billing_export_bulk_unbill_success', { count: ids.length }));
                },
            });

            return;
        }

        router.delete(route('finance.billing-export.records.destroy', ids[0]), {
            preserveScroll: true,
            onSuccess: () => {
                selectedRecordIds.value = selectedRecordIds.value.filter((id) => id !== ids[0]);
                successAlert(trans('finance.billing_export_unbill_success'));
            },
        });
    };

    const createBillingRecordColumns = () => [
        {
            header: () =>
                h(BaseCheckbox, {
                    inputId: 'select_all_billing_records',
                    label: '',
                    modelValue: selectAllModel.value,
                    'onUpdate:modelValue': (value: boolean) => {
                        selectAllModel.value = value;
                    },
                }),
            accessorKey: 'select',
            enableSorting: false,
            meta: { align: 'center' },
            cell: ({ row }: { row: { original: StudentBillingRecord } }) => {
                const id = resolveRecordId(row.original);

                return h(BaseCheckbox, {
                    inputId: `select_billing_record_${id}`,
                    label: '',
                    modelValue: selectedRecordIds.value.includes(id),
                    'onUpdate:modelValue': (checked: boolean) => {
                        if (checked) {
                            if (!selectedRecordIds.value.includes(id)) {
                                selectedRecordIds.value = [...selectedRecordIds.value, id];
                            }
                        } else {
                            selectedRecordIds.value = selectedRecordIds.value.filter((selectedId) => selectedId !== id);
                        }
                    },
                });
            },
        },
        {
            header: trans('finance.billing_export_student_number'),
            accessorKey: 'attributes.studentNumber',
        },
        {
            header: trans('finance.billing_export_student_name'),
            accessorKey: 'attributes.studentName',
        },
        {
            header: trans('finance.billing_export_phase'),
            accessorKey: 'attributes.phase',
        },
        {
            header: trans('finance.billing_export_period'),
            accessorKey: 'attributes.billingPeriod',
        },
        {
            header: trans('finance.billing_export_status'),
            accessorKey: 'attributes.statusLabel',
        },
        {
            header: trans('finance.billing_export_in_pastel_column'),
            accessorKey: 'attributes.pastelLinked',
            cell: ({ row }: { row: { original: StudentBillingRecord } }) => {
                return row.original.attributes.pastelLinked
                    ? trans('finance.billing_export_yes')
                    : trans('finance.billing_export_no');
            },
        },
        {
            header: trans('finance.billing_export_exported_at'),
            accessorKey: 'attributes.exportedAt',
            cell: ({ row }: { row: { original: StudentBillingRecord } }) => {
                const exportedAt = row.original.attributes.exportedAt;

                return exportedAt ? formatDate(exportedAt, 'LLL') : '—';
            },
        },
        {
            header: trans('finance.billing_export_billed_by'),
            accessorKey: 'attributes.billedByName',
            cell: ({ row }: { row: { original: StudentBillingRecord } }) => {
                return row.original.attributes.billedByName || '—';
            },
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }: { row: { original: StudentBillingRecord } }) => {
                const record = row.original;
                const buttons = [];

                if (isExported(record)) {
                    buttons.push(
                        actionButton({
                            title: trans('finance.billing_export_mark_billed'),
                            variant: ColorVariant.primary_outline,
                            onClick: () => {
                                void markBilled([resolveRecordId(record)]);
                            },
                        }),
                        actionButton({
                            title: trans('finance.billing_export_mark_failed'),
                            variant: ColorVariant.danger_outline,
                            onClick: () => {
                                void markFailed([resolveRecordId(record)]);
                            },
                        }),
                    );
                }

                if (isBilled(record)) {
                    buttons.push(
                        actionButton({
                            title: trans('finance.billing_export_unbill'),
                            variant: ColorVariant.danger_outline,
                            onClick: () => {
                                void unbill([resolveRecordId(record)], false);
                            },
                        }),
                    );
                }

                return h('div', { class: 'flex flex-wrap justify-end gap-1' }, buttons);
            },
        },
    ];

    return {
        createBillingRecordColumns,
        markBilled,
        markFailed,
        unbill,
        selectedRecordIds,
        selectedExportedIds,
        selectedBilledIds,
        selectAllModel,
        selectedCount,
        clearSelection,
    };
}
