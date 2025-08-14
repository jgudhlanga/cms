import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { hasAbility } from '@/lib/permissions';
import { StudentProgram } from '@/types/students';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { errorAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { InertiaForm } from '@inertiajs/vue3';
import { getIdParams } from '@/lib/utils';
import { buildFormOptions } from '@/lib/forms';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import HttpService from '@/services/http.service';
import { Enrolment } from '@/types/enrolments';

export const useStudentApplications = () => {
    const { moreActionButton, actionButton, textLink } = useDataTables();
    const studentAbility = 'manageOwnStudentProgramDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const { formatDate, navigateTo } = useUtils();
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
                            action: () => navigateTo(route('portal.application.view', row.original.id)),
                        },
                    ]);
                },
            },
        ];
    };

    const onUploadPopModal = () => {
        openModal({ name: APP_MODULE_KEYS.upload_proof_of_payment, edit: null });
    };

    const uploadProofOfPayment = (form: InertiaForm<any>, application: StudentProgram) => {
        const successMessage = () => trans('trans.proof_of_payment_uploaded');
        const errorMessage = () => trans('trans.proof_of_payment_failure');
        try {
            const id = getIdParams(application?.id?.toString() ?? '');
            form.post(route('students.upload-proof-of-payment', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.upload_proof_of_payment));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

     const approveApplication = async (applicationId: string, nextStepId: string) => {
        const successMessage = () => trans('trans.application_approval_success');
        const errorMessage = () => trans('trans.application_approval_failure');
        try {
            await HttpService.post(route('students.approve-application', {student_program: applicationId, department_application_step: nextStepId}), {});
            successAlert(successMessage())
        } catch (error: any) {
             errorAlert(errorMessage())
        }
    };

      const bulkApproveApplication = (institutionDepartmentId: string, nextStepId: string) => {
        console.log('Bulk approval')
        /* const successMessage = () => trans('trans.proof_of_payment_uploaded');
        const errorMessage = () => trans('trans.proof_of_payment_failure');
        try {
            const id = getIdParams(application?.id?.toString() ?? '');
            form.post(route('students.upload-proof-of-payment', {}), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.upload_proof_of_payment));
        } catch (error: any) {
            form.setError(error.format());
        } */
    };

    return {
        createStudentApplicationColumns,
        allowed,
        onUploadPopModal,
        uploadProofOfPayment,
        approveApplication,
        bulkApproveApplication,
    };
};

