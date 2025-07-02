import { useDataTables } from '@/composables/core/useDataTables';
import { getIdParams } from '@/lib/utils';
import { Staff } from '@/types/staff';

import { trans_choice } from 'laravel-vue-i18n';

export const useStaffs = () => {
    const { moreActionButton, textLink } = useDataTables();

    const createStaffColumns = () => {
        return [
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: Staff } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('department-courses.show', id), row.original.attributes?.course);
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Staff } }) => {
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                        {
                            key: 'edit',
                            action: () => {},
                        },
                        {
                            key: 'archive',
                            action: () => {},
                        },
                        {
                            key: 'restore',
                            action: () => {},
                        },
                        {
                            key: 'delete',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    return {
        createStaffColumns,
    };
};
