import { useDataTables } from '@/composables/core/useDataTables';
import { Student } from '@/types/students';
import { trans_choice } from 'laravel-vue-i18n';
import { Enrolment } from '@/types/enrolments';

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

    const getApplicationStatus = (application: Enrolment) => {
        return application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep;
    };

    const hasOfferLetter = (application: Enrolment) => getApplicationStatus(application)?.toLowerCase() === 'accepted';

    const statusMessage = (application: Enrolment) => {
        const step = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? '';
        switch (step) {
            case 'Review':
                return 'Your application has been submitted and is awaiting review.';
            case 'Requirements':
                return 'Your application is currently under review by the admissions team. Please present the required documents (Academic certificates and transcripts, National ID, Birth certificate) at the Old Administration Block Boardroom, located in the Civil and Mechanical Engineering Section, during working hours';
            case 'Accepted':
                return 'Congratulations! Your application has been accepted.';
            case 'Rejected':
                return 'We regret to inform you that your application has been rejected.';
            case 'Waitlisted':
                return 'Due to the high number of qualified applicants this year, your name has been placed on the waiting list. This means that your admission is currently pending final placement confirmation.';
            case 'Enrolled':
                return 'We regret to inform you that your application has been rejected.';
            default:
                return 'Status information is currently unavailable.';
        }
    };
    return {
        createStudentColumns, getApplicationStatus, hasOfferLetter, statusMessage
    };
};
