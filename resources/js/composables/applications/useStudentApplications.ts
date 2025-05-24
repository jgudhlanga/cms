import { useDataTables } from '@/composables/core/useDataTables';
import { usePage } from '@inertiajs/vue3';
import { Auth } from '@/types';
import { trans_choice } from 'laravel-vue-i18n';
import { AddressType } from '@/types/settings';
import { getIdParams } from '@/lib/utils';


export function useStudentApplications() {
    const { moreActionButton, onDelete, onForceDelete, onRestore, textLink } = useDataTables();

    const applicationsTable = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AddressType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.address_type', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => {} },
                        { key: 'view', action: () => {} },
                        { key: 'archive',  action: () => {} },
                        { key: 'restore', action: () => {}},
                        { key: 'delete',action: () => {}},
                    ]);
                },
            },
        ];
    };
    return {applicationsTable};
}
