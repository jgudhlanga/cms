import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Module } from '@/types/acl';
import type { Link } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useAcademicCalendars = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createTableColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.type', 1), accessorKey: 'attributes.type' },
            { header: trans_choice('trans.year', 1), accessorKey: 'attributes.year' },
            { header: trans('trans.opening_date'), accessorKey: 'attributes.openingDate' },
            { header: trans('trans.closing_date'), accessorKey: 'attributes.closingDate' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Module } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.module', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:modules'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:modules'], route('modules.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:modules'], route('modules.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:modules'], route('modules.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'institution',
            href: route('institution.index'),
        },
        { transKey: 'institution_setup', href: route('institution.setup') },
        { transChoiceKey: 'academic_calendar' },
    ];

    const onOpenModal = (can: boolean, edit?: Module) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.modules, edit: edit });
    };

    return {
        createTableColumns,
        breadcrumbs,
        onOpenModal,
    };
};
