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
import {
    BulkApplicationApprovalParams,
    BulkUpdatePaymentStatusParams,
    Enrolment,
    PaymentProofPreview
} from '@/types/enrolments';
import { StudentProgram } from '@/types/students';
import { InertiaForm, router, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ColorVariant } from '@/enums/colors';

export const useStudentApplications = () => {
    const {  actionButton, textLink } = useDataTables();
    const studentAbility = 'manageOwnStudentProgramDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const { formatDate, navigateTo } = useUtils();
    const createStudentApplicationColumns = () => {
        return [
            {
                header: trans_choice('trans.program', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return textLink(
                        route('portal.application.view', row.original.id),
                        row.original?.attributes?.course ?? '',
                    );
                },
            },
            {
                header: trans_choice('trans.department', 1),
                accessorKey: 'attributes.department',
            },
            {
                header: trans_choice('trans.level', 1),
                accessorKey: 'attributes.level',
            },
            {
                header: trans('trans.application_date'),
                accessorKey: 'applicationDate',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const applicationDate = row.original?.attributes?.createdAt ?? '';
                    return applicationDate ? formatDate(applicationDate, 'L') : '---';
                },
            },
            {
                header: trans('trans.update_date'),
                accessorKey: 'updateDate',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const updateDate = row.original?.attributes?.updatedAt ?? '';
                    return updateDate ? formatDate(updateDate, 'L') : '---';
                },
            },
            {
                header: `${trans_choice('trans.application', 1)} ${trans_choice('trans.status', 1)}`,
                accessorKey: 'applicationStatus',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Enrolment } }) => {
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
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return actionButton({
                        title: 'Edit Application',
                        variant: ColorVariant.success,
                        onClick: () => {
                            router.visit(route('portal.application.edit', row.original.id), {
                                preserveState: false,
                                preserveScroll: false,
                                replace: true,
                            });
                        },
                    });
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
        if (registrationFeePaymentRequired(currentStep) && !enrolment.attributes.registrationFeeConfirmed) {
            const applicationFeeRequiredMessage = () => trans('trans.application_fee_required');
            errorAlert(applicationFeeRequiredMessage());
            return;
        }

        if (tuitionFeePaymentRequired(currentStep) && !enrolment.attributes.tuitionFeeConfirmed) {
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
        if (!allRegistrationFeesPaid(enrolments) && registrationFeePaymentRequired(step)) {
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
           warningDialog(() => {
                 HttpService.post(route('students.bulk-approve-applications', institutionDepartmentId), params);
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const bulkUpdatePaymentStatus = async (
        institutionDepartmentId: string,
        step: DepartmentApplicationStep,
        enrolments: Enrolment[],
        params: BulkUpdatePaymentStatusParams,
    ) => {
        if (!allRegistrationFeesPaid(enrolments) && registrationFeePaymentRequired(step)) {
            const applicationFeeRequiredMessage = () => trans('trans.all_application_fee_required_to_be_paid');
            errorAlert(applicationFeeRequiredMessage());
            return;
        }

        if (!allTuitionFeesPaid(enrolments) && tuitionFeePaymentRequired(step)) {
            const tuitionFeeRequiredMessage = () => trans('trans.all_tuition_fee_required_to_be_paid');
            errorAlert(tuitionFeeRequiredMessage());
            return;
        }
        const successMessage = () => trans('trans.bulk_payment_status_update_success');
        const errorMessage = () => trans('trans.bulk_payment_status_update_failure');
        const alertMessage = () =>
            trans('trans.mark_all_payment_as', {
                step: step?.attributes?.workflowStep,
                as: params.field_value ? trans('trans.paid') : trans('trans.unpaid'),
            });
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.bulk-update-payment-statuses', institutionDepartmentId), params);
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            }, alertMessage());
        } catch {
            errorAlert(errorMessage());
        }
    };

    const confirmRegistrationFeeAsPaid = async (enrolment: Enrolment, paid: boolean) => {
        const successMessage = () => trans('trans.application_fee_payment_message', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        const errorMessage = () => trans('trans.application_fee_payment_failure', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        const markAsMessage = () => trans('trans.mark_payment_as', { as: paid ? trans('trans.unpaid') : trans('trans.paid') });
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.confirm-registration-fee-payment', { student_program: enrolment.id?.toString() ?? '' }), {});
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            }, markAsMessage());
        } catch {
            errorAlert(errorMessage());
        }
    };

    const confirmTuitionFeeAsPaid = async (enrolment: Enrolment, paid: boolean) => {
        const successMessage = () => trans('trans.tuition_fee_payment_message', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        const errorMessage = () => trans('trans.tuition_fee_payment_failure', { action: paid ? trans('trans.unpaid') : trans('trans.paid') });
        try {
            warningDialog(async () => {
                await HttpService.post(route('students.confirm-tuition-fee-payment', { student_program: enrolment?.id?.toString() ?? '' }), {});
                successAlert(successMessage());
                router.visit(window.location.href, { replace: true });
            });
        } catch {
            errorAlert(errorMessage());
        }
    };

    const registrationFeePaymentRequired = (step: DepartmentApplicationStep) => {
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

    const allRegistrationFeesPaid = (enrolments: Enrolment[]): boolean => {
        return enrolments.every((e) => Number(e?.relationships?.registrationReceipt?.attributes?.amount) > 0);
    };

    const allTuitionFeesPaid = (enrolments: Enrolment[]): boolean => {
        return enrolments.every((e) => Number(e?.relationships?.tuitionReceipt?.attributes?.amount) > 0);
    };

    /* const allProofOfPaymentUploaded = (enrolments: Enrolment[], type: 'application-fee' | 'tuition-fee'): boolean => {
        if (type === 'application-fee') {
            return enrolments.every((e) => Number(e.attributes.applicationFeeProofOfPaymentId) > 0);
        } else if (type === 'tuition-fee') {
            return enrolments.every((e) => Number(e.attributes.tuitionFeeProofOfPaymentId) > 0);
        }
        return false;
    };*/

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
        confirmRegistrationFeeAsPaid,
        confirmTuitionFeeAsPaid,
        registrationFeePaymentRequired,
        tuitionFeePaymentRequired,
        proofOfPaymentRequired,
        allRegistrationFeesPaid,
        allTuitionFeesPaid,
        onPaymentProofModal,
        awaitApplicationPaymentProof,
        awaitTuitionPaymentProof,
        canApproveWorkflowStepApplications,
        bulkUpdatePaymentStatus,
    };
};
