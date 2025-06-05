import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Division, } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useDivisions = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, orderButtons } = useDataTables();
    const createDivisionColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.position', 1),
                accessorKey: 'attributes.position',
                meta: { align: 'center' }
            },
            {
                header: trans('trans.order'),
                accessorKey: 'order',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Division } }) => orderButtons(),
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Division } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.division', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('divisions.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('divisions.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('divisions.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'settings',
            href: route('settings.index'),
        },
        { transChoiceKey: 'division' },
    ];

    const saveDivision = (form: InertiaForm<any>, division?: Division) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.division', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.division', 1) });
            if (division) {
                const id = getIdParams(division.id?.toString() ?? '');
                form.put(route('divisions.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.divisions));
            } else {
                form.post(route('divisions.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.divisions));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, division?: Division) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.divisions, edit: division });
    };

    return {
        createDivisionColumns,
        breadcrumbs,
        onOpenModal,
        saveDivision,
    };
};
