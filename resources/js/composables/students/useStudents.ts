import { useDataTables } from '@/composables/core/useDataTables';
import { Student } from '@/types/students';
import { trans_choice } from 'laravel-vue-i18n';

export const useStudents = () => {
    const { moreActionButton, textLink } = useDataTables();

    const createStudentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Student } }) => {
                    return textLink(route('portal.programs'), row.original?.relationships?.user?.attributes?.name ?? '');
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Student } }) => {
                    return moreActionButton(false, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    return {
        createStudentColumns,
    };
};
