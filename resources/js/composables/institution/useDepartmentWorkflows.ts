import { useUtils } from '@/composables/core/useUtils';
import { closeModal, errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { toggleFormLoader } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useDepartmentWorkflows = () => {
    const openDepartmentApplicationStepsModal = (departmentSteps: Array<string | undefined | null> | null) => {
        if (!hasAbility('department-setup:workflows')) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_application_steps, edit: departmentSteps });
    };

    const openDepartmentWorkflowActionModal = (step: DepartmentApplicationStep) => {
        if (!hasAbility('department-setup:workflows')) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_workflow_actions, edit: step });
    };
    const getName = () => trans_choice('trans.application_step', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const { navigateTo } = useUtils();
    const syncDepartmentApplicationSteps = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            form.post(route('department-application-steps.sync', institutionDepartmentId), {
                preserveScroll: true,
                onSuccess: () => {
                    successAlert(successMessage());
                    closeModal(APP_MODULE_KEYS.department_application_steps);
                },
                onError: () => errorAlert(errorMessage()),
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const syncWorkflowStepActionMetadata = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
             form.post(route('department-application-steps.sync-metadata', institutionDepartmentId), {
                preserveScroll: true,
                onSuccess: () => {
                    successAlert(successMessage());
                    closeModal(APP_MODULE_KEYS.department_workflow_actions);
                },
                onError: () => errorAlert(errorMessage()),
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const updateDepartmentApplicationSteps = (departmentApplicationStepId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            form.post(route('department-application-steps.update', departmentApplicationStepId), {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => {
                    successAlert(successMessage());
                    navigateTo(route('institution-departments.show', getIdParams(institutionDepartmentId)));
                },
                onError: () => {
                    errorAlert(errorMessage());
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        openDepartmentApplicationStepsModal,
        syncDepartmentApplicationSteps,
        updateDepartmentApplicationSteps,
        openDepartmentWorkflowActionModal,
        syncWorkflowStepActionMetadata,
    };
};
