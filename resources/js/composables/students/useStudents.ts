import { useDataTables } from '@/composables/core/useDataTables';
import { Enrolment } from '@/types/enrolments';
import { Student } from '@/types/students';
import { trans_choice } from 'laravel-vue-i18n';
import { useUtils } from '@/composables/core/useUtils';

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
        //const step = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep;
        //return step?.toLowerCase() === 'review' ? 'Unsuccessful' : step;
    };

    const hasOfferLetter = (application: Enrolment) => getApplicationStatus(application)?.toLowerCase() === 'accepted';

    const statusMessage = (application: Enrolment) => {
        const workflowStep = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? '';
        //const step =  workflowStep?.toLowerCase() === 'review' ? 'Unsuccessful' : workflowStep;
        switch (workflowStep) {
            case 'Review':
                return 'Your application has been submitted and is awaiting review.';
            case 'Requirements':
                return 'Your application is currently under review by the admissions team. Please present the required documents (Academic certificates and transcripts, National ID, Birth certificate) at the Old Administration Block Boardroom, located in the Civil and Mechanical Engineering Section, during working hours';
            case 'Accepted':
                return 'Congratulations! Your application has been accepted.';
            case 'Rejected':
                return 'We regret to inform you that your application has been unsuccessful.';
            case 'Waitlisted':
                return 'Due to the high number of qualified applicants this year, your name has been placed on the waiting list. This means that your admission is currently pending final placement confirmation.';
            case 'Enrolled':
                return 'Congratulations! Your are enrolled.';
            case 'Unsuccessful':
                return 'We regret to inform you that your application has been unsuccessful.';
            default:
                return 'Status information is currently unavailable.';
        }
    };
    const showCreateNewProgramButton = (applications: Enrolment[], currentIntakePeriod: string): boolean => {
        return !applications.some((application) => {
            const workflowStep = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep;

            const intakePeriodId = application?.attributes?.intakePeriodId;

            return workflowStep === 'Accepted' || String(intakePeriodId) === String(currentIntakePeriod);
        });
    };

    const showEditProgramButton = (application: Enrolment, currentIntakePeriod: string): boolean => {
        const { isItTrue } = useUtils();
        const workflowStep = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep;
        const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);
        return workflowStep !== 'Accepted' && String(application?.attributes?.intakePeriodId) === String(currentIntakePeriod) && !verificationMode;
    };

    return {
        createStudentColumns,
        getApplicationStatus,
        hasOfferLetter,
        statusMessage,
        showCreateNewProgramButton,
        showEditProgramButton,
    };
};
