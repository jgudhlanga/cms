import { useDataTables } from '@/composables/core/useDataTables';
import { hasAbility } from '@/lib/permissions';
import { StudentProgram } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useStudentPrograms = () => {
    const { moreActionButton, actionButton, textLink } = useDataTables();
    const getName = () => trans_choice('trans.program', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const studentAbility = 'manageOwnStudentProgramDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const createStudentProgramColumns = () => {
        return [
            {
                header: trans_choice('trans.program', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return textLink(route('portal.programs'), row.original?.relationships?.departmentCourse?.attributes?.course ?? '');
                },
            },
            {
                header: trans_choice('trans.department', 1),
                accessorKey: 'relationships.institutionDepartment.attributes.department',
            },
            {
                header: trans_choice('trans.level', 1),
                accessorKey: 'relationships.departmentLevel.attributes.level',
            },
           /* {
                header: trans('trans.start_date'),
                accessorKey: 'startDate',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return '---';
                },
            },
            {
                header: trans('trans.end_date'),
                accessorKey: 'endDate',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return '---';
                },
            },*/
            {
                header: `${trans_choice('trans.application', 1)} ${trans_choice('trans.status', 1)}`,
                accessorKey: 'applicationStatus',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    // process some status meta data
                    const step = row.original?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? '';
                    return actionButton({
                        title: step,
                        onClick: () => {},
                    });
                },
            },
           /* {
                header: `${trans_choice('trans.program', 1)} ${trans_choice('trans.status', 1)}`,
                accessorKey: 'programStatus',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return '---';
                },
            },*/
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: StudentProgram } }) => {
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
        createStudentProgramColumns,
        allowed,
    };
};
