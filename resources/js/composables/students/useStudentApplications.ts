import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, openModal, successAlert, warningDialog } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { PageProps } from '@/types';
import { Role } from '@/types/acl';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { BulkApplicationApprovalParams, Enrolment, PaymentProofPreview } from '@/types/enrolments';
import { StudentProgram } from '@/types/students';
import { InertiaForm, router, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useStudentApplications = () => {
    const { moreActionButton, actionButton, textLink } = useDataTables();
    const studentAbility = 'manageOwnStudentProgramDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const { formatDate, navigateTo, isItTrue } = useUtils();
    const createStudentApplicationColumns = () => {
        return [
            {
                header: trans_choice('trans.program', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    return textLink(
                        route('portal.application.view', row.original.id),
                        row.original?.relationships?.departmentCourse?.attributes?.course ?? '',
                    );
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
                    return applicationDate ? formatDate(applicationDate, 'L') : '---';
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
                        onClick: () => navigateTo(route('portal.application.view', row.original.id)),
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

    const onPaymentProofModal = (preview: PaymentProofPreview) => {
        openModal({ name: APP_MODULE_KEYS.preview_payment_proof, edit: preview });
    };

    const uploadProofOfPayment = (form: InertiaForm<any>, application: StudentProgram) => {
        const successMessage = () => trans('trans.proof_of_payment_uploaded');
        const errorMessage = () => trans('trans.proof_of_payment_failure');
        try {
            const id = getIdParams(application?.id?.toString() ?? '');
            form.post(
                route('students.upload-proof-of-payment', id),
                buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.upload_proof_of_payment),
            );
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const approveApplication = async (enrolment: Enrolment, nextStepId: string, currentStep: DepartmentApplicationStep) => {
        if (applicationFeePaymentRequired(currentStep) && !enrolment.attributes.applicationFeePaid) {
            const applicationFeeRequiredMessage = () => trans('trans.application_fee_required');
            errorAlert(applicationFeeRequiredMessage());
            return;
        }

        if (tuitionFeePaymentRequired(currentStep) && !enrolment.attributes.tuitionFeePaid) {
            const tuitionFeeRequiredMessage = () => trans('trans.tuition_fee_required');
            errorAlert(tuitionFeeRequiredMessage());
            return;
        }
        const successMessage = () => trans('trans.application_approval_success');
        const errorMessage = () => trans('trans.application_approval_failure');
        try {
            warningDialog(async () => {
                await HttpService.post(
                    route('students.approve-application', { student_program: enrolment.id.toString(), department_application_step: nextStepId }),
                    {},
                );
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const bulkApproveApplication = async (
        institutionDepartmentId: string,
        params: BulkApplicationApprovalParams,
        enrolments: Enrolment[],
        step: DepartmentApplicationStep,
    ) => {
        if (!allApplicationFeesPaid(enrolments) && applicationFeePaymentRequired(step)) {
            const applicationFeeRequiredMessage = () => trans('trans.all_application_fee_required_to_be_paid');
            errorAlert(applicationFeeRequiredMessage());
            return;
        }

        if (!allTuitionFeesPaid(enrolments) && tuitionFeePaymentRequired(step)) {
            const tuitionFeeRequiredMessage = () => trans('trans.all_tuition_fee_required_to_be_paid');
            errorAlert(tuitionFeeRequiredMessage());
            return;
        }
        const successMessage = () => trans('trans.bulk_application_approval_success');
        const errorMessage = () => trans('trans.bulk_application_approval_failure');
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.bulk-approve-applications', institutionDepartmentId), params);
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const markApplicationFeeAsPaid = async (enrolment: Enrolment, paid: boolean) => {
        if (!allProofOfPaymentUploaded([enrolment], 'application-fee')) {
            const errorMessage = () => trans('trans.no_proof_of_application_fee_payment_uploaded');
            errorAlert(errorMessage());
            return;
        }
        const successMessage = () => trans('trans.application_fee_payment_message', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        const errorMessage = () => trans('trans.application_fee_payment_failure', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.mark-application-fee-payment', { student_program: enrolment.id?.toString() ?? '' }), {});
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const markTuitionFeeAsPaid = async (enrolment: Enrolment, paid: boolean) => {
        if (!allProofOfPaymentUploaded([enrolment], 'tuition-fee')) {
            const errorMessage = () => trans('trans.no_proof_of_tuition_fee_payment_uploaded');
            errorAlert(errorMessage());
            return;
        }
        const successMessage = () => trans('trans.tuition_fee_payment_message', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        const errorMessage = () => trans('trans.tuition_fee_payment_failure', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.mark-tuition-fee-payment', { student_program: enrolment?.id?.toString() ?? '' }), {});
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const applicationFeePaymentRequired = (step: DepartmentApplicationStep) => {
        return step?.relationships?.metadata?.actions?.some((action) => action.action == 'verify-application-fee-payment-with-accounts');
    };

    const awaitApplicationPaymentProof = (step: DepartmentApplicationStep) => {
        return step?.attributes?.slug == 'awaiting-application-fee-payment';
    };

    const awaitTuitionPaymentProof = (step: DepartmentApplicationStep) => {
        return step?.attributes?.slug == 'awaiting-tuition-fee-payment';
    };

    const tuitionFeePaymentRequired = (step: DepartmentApplicationStep) => {
        return step?.relationships?.metadata?.actions?.some((action) => action.action == 'verify-tuition-fee-payment-with-accounts');
    };

    const proofOfPaymentRequired = (step: DepartmentApplicationStep) => {
        return step?.relationships?.metadata?.actions?.some((action) => action.action == 'upload-proof-of-payment');
    };

    const allApplicationFeesPaid = (enrolments: Enrolment[]): boolean => {
        return enrolments.every((e) => isItTrue(e.attributes.applicationFeePaid));
    };

    const allTuitionFeesPaid = (enrolments: Enrolment[]): boolean => {
        return enrolments.every((e) => isItTrue(e.attributes.tuitionFeePaid));
    };

    const allProofOfPaymentUploaded = (enrolments: Enrolment[], type: 'application-fee' | 'tuition-fee'): boolean => {
        if (type === 'application-fee') {
            return enrolments.every((e) => Number(e.attributes.applicationFeeProofOfPaymentId) > 0);
        } else if (type === 'tuition-fee') {
            return enrolments.every((e) => Number(e.attributes.tuitionFeeProofOfPaymentId) > 0);
        }
        return false;
    };

    const canApproveWorkflowStepApplications = (step: DepartmentApplicationStep): boolean => {
        const { user } = usePage<PageProps>().props?.auth;
        if (!user) {
            return false; // no user means no approval rights
        }
        // super roles always have access
        const roles = user.relationships?.roles ?? [];
        if (roles.some((role: Role) => ['super-user', 'super-administrator'].includes(role.attributes.slug))) {
            return true;
        }
        // get user role IDs (normalize to number, filter out null/undefined)
        const userRoleIds = user?.relationships?.roles?.map((role: Role) => Number(role.id)) ?? [];

        // normalize step role IDs to number (filter out null/undefined)
        const stepRoleIds = (step?.relationships?.metadata?.roleIds ?? []).filter((id): id is string => id != null).map((id) => Number(id));
        return userRoleIds.some((roleId: any) => stepRoleIds.includes(roleId));
    };

    return {
        createStudentApplicationColumns,
        allowed,
        onUploadPopModal,
        uploadProofOfPayment,
        approveApplication,
        bulkApproveApplication,
        markApplicationFeeAsPaid,
        markTuitionFeeAsPaid,
        applicationFeePaymentRequired,
        tuitionFeePaymentRequired,
        proofOfPaymentRequired,
        allApplicationFeesPaid,
        allTuitionFeesPaid,
        onPaymentProofModal,
        allProofOfPaymentUploaded,
        awaitApplicationPaymentProof,
        awaitTuitionPaymentProof,
        canApproveWorkflowStepApplications,
    };
};
