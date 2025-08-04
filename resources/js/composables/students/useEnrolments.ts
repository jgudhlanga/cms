import { useDataTables } from '@/composables/core/useDataTables';
import { Student, StudentProgram } from '@/types/students';
import { trans_choice } from 'laravel-vue-i18n';

export const useEnrolments = () => {
    const { moreActionButton, textLink } = useDataTables();

    const enrolmentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    const studentName = row.original?.relationships?.student?.relationships?.user?.attributes?.name
                    return textLink(route('portal.programs'), studentName ?? '');
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
        enrolmentColumns,
    };
};
