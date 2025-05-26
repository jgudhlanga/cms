import { useDataTables } from '@/composables/core/useDataTables';
import { usePage } from '@inertiajs/vue3';
import { Auth } from '@/types';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { AddressType } from '@/types/settings';
import { getIdParams } from '@/lib/utils';
import { Step } from '@/types/forms';


export function useStudentPortal() {
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

    const steps: Step[] = [
        { step: 1, title: trans('trans.personal_details'), description: 'trans.personal_details_description' },
        { step: 2, title: trans('trans.contact_details'), description: 'trans.contact_details_description' },
        { step: 3, title: trans('trans.next_of_kin'), description: 'trans.next_of_kin_description' },
        { step: 4, title: trans('trans.programs'), description: 'trans.program_description' },
        { step: 5, title: trans('trans.confirmation'), description: 'trans.confirmation_description' },
    ];
    return {applicationsTable, steps};
}
