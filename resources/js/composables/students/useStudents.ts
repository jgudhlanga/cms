import { useDataTables } from '@/composables/core/useDataTables';
import { Enrolment } from '@/types/enrolments';
import { Student, StudentFiltersState } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { useUtils } from '@/composables/core/useUtils';
import { InertiaForm } from '@inertiajs/vue3';
import { errorAlert, successAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import { ref } from 'vue';
import HttpService from '@/services/http.service';

export const useStudents = () => {
    const { moreActionButton, textLink } = useDataTables();

    const isLoading = ref(false);

    const createStudentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Student } }) => {
                    return textLink(route('students.show', String(row.original?.id)), row.original?.relationships?.user?.attributes?.name ?? '');
                },
            },
            {header: trans_choice('trans.id_number',1), accessorKey: 'attributes.idNumber',},
            {header: trans_choice('trans.student_number',1), accessorKey: 'attributes.studentNumber',},
            {
                header: trans_choice('trans.gender', 1),
                accessorKey: 'gender',
                cell: ({ row }: { row: { original: Student } }) => {
                    return row.original.attributes?.gender ?? '--';
                },
            },
            {
                header: trans_choice('trans.department', 1),
                accessorKey: 'department',
                cell: ({ row }: { row: { original: Student } }) => {
                    return row.original.attributes?.department ?? '--';
                },
            },
            {
                header: trans_choice('trans.level', 1),
                accessorKey: 'level',
                cell: ({ row }: { row: { original: Student } }) => {
                    return row.original.attributes?.level ?? '--';
                },
            },
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: Student } }) => {
                    return row.original.attributes?.course ?? '--';
                },
            }, 
            {
                header: trans_choice('trans.mode_of_study', 1),
                accessorKey: 'modeOfStudy',
                cell: ({ row }: { row: { original: Student } }) => {
                    return row.original.attributes?.modeOfStudy ?? '--';
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

    const hasOfferLetter = (application: Enrolment) => getApplicationStatus(application)?.toLowerCase() === 'accepted' || getApplicationStatus(application)?.toLowerCase() === 'enrolled';

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

    const updateProgram = (applicationId: string, form: InertiaForm<any>) => {
        try {
            form.put(route('students.program-update', applicationId), {
                onSuccess: () => {
                    successAlert('Program successfully updated');
                },
                onError: (errors: any) => {
                    if (Object.keys(errors).length) {
                        const allErrors = Object.values(errors).join('\n');
                        errorAlert(allErrors);
                    } else {
                        errorAlert('An unexpected error happened, program could not be updated');
                    }
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };


    const studentListMergeOptions = { booleanParamKeys: ['with_trashed'] };

    const fetchStudents = async (filters: StudentFiltersState = {}, paginatorUrl?: string) => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('v1.students.index');
            const path = mergeQueryParamsIntoRequestPath(baseUrl, filters as Record<string, unknown>, studentListMergeOptions);
            return await HttpService.get(path);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        createStudentColumns,
        getApplicationStatus,
        hasOfferLetter,
        statusMessage,
        showCreateNewProgramButton,
        showEditProgramButton,
        updateProgram,
        fetchStudents,
        isLoading,
    };
};
