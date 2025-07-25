import { useDataTables } from '@/composables/core/useDataTables';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth } from '@/types';
import { ApiFilterResponse } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { z } from 'zod';

export const useIntakePeriods = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createIntakePeriodColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.start_date'), accessorKey: 'attributes.startDate' },
            { header: trans('trans.end_date'), accessorKey: 'attributes.endDate' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: IntakePeriod } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.intake_period', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('intake-periods.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('intake-periods.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('intake-periods.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const formSchema = () =>
        z
            .object({
                name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.name', 1) })),
                start_date: z
                    .union([z.string(), z.date()])
                    .transform((val) => (typeof val === 'string' ? val : val.toISOString().split('T')[0]))
                    .refine((val) => !isNaN(Date.parse(val)), {
                        message: trans('trans.date_must_be_valid', { field: trans('trans.start_date') }),
                    }),
                end_date: z
                    .union([z.string(), z.date()])
                    .transform((val) => (typeof val === 'string' ? val : val.toISOString().split('T')[0]))
                    .refine((val) => !isNaN(Date.parse(val)), {
                        message: trans('trans.date_must_be_valid', { field: trans('trans.end_date') }),
                    }),
            })
            .refine(
                (data) => {
                    return new Date(data.end_date) >= new Date(data.start_date);
                },
                {
                    message: trans('trans.end_date_start_date_validation'),
                    path: ['end_date'],
                },
            );
    const saveIntakePeriod = (form: InertiaForm<any>, intakePeriod?: IntakePeriod) => {
        const success = trans('trans.item_saved', { item: trans_choice('trans.intake_period', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.intake_period', 1) });
        if (intakePeriod) {
            const id = getIdParams(intakePeriod.id?.toString() ?? '');
            form.put(route('intake-periods.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.intake_periods));
        } else {
            form.post(route('intake-periods.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.intake_periods));
        }
    };
    const onOpenModal = (can: boolean, intakePeriod?: IntakePeriod) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.intake_periods, edit: intakePeriod });
    };

    const isLoading = ref(false);
    const intakePeriods = ref<ApiFilterResponse | null>(null);
    const listIntakePeriods = async (url: string) => {
        try {
            isLoading.value = true;
            intakePeriods.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.intake_period', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        createIntakePeriodColumns,
        onOpenModal,
        saveIntakePeriod,
        listIntakePeriods,
        formSchema,
        isLoading,
        intakePeriods,
    };
};
