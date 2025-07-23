import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { hasAbility } from '@/lib/permissions';
import { StudentProgram } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useStudentApplications = () => {
    const { moreActionButton, actionButton, textLink } = useDataTables();
    const studentAbility = 'manageOwnStudentProgramDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const { formatDate } = useUtils();
    const createStudentApplicationColumns = () => {
        return [
            {
                header: trans_choice('trans.program', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return textLink(route('portal.application.view', row.original.id), row.original?.relationships?.departmentCourse?.attributes?.course ?? '');
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
            {
                header: trans('trans.application_date'),
                accessorKey: 'applicationDate',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    const applicationDate = row.original?.attributes?.createdAt ?? '';
                    return applicationDate ? formatDate(applicationDate , 'L') : '---';
                },
            },
            {
                header: trans('trans.update_date'),
                accessorKey: 'updateDate',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    const updateDate = row.original?.attributes?.updatedAt ?? '';
                    return updateDate ? formatDate(updateDate, 'L') : '---';
                },
            },
            {
                header: `${trans_choice('trans.application', 1)} ${trans_choice('trans.status', 1)}`,
                accessorKey: 'applicationStatus',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    const step = row.original?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? '';
                    return actionButton({
                        title: step,
                        onClick: () => {},
                    });
                },
            },
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
        createStudentApplicationColumns,
        allowed,
    };
};
