import { closeModal, errorAlert, successAlert } from '@/lib/alerts';
import { useLoadersStore } from '@/store/core/loaders.store';
import { InertiaForm, router } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ZodObject } from 'zod';
import { SelectOption } from '@/types/utils';

function clearFormErrors(inertiaForm: InertiaForm<any>, field: any) {
    inertiaForm.clearErrors(field);
}

const toggleFormLoader = (processing: boolean) => {
    const { formProcessing } = storeToRefs(useLoadersStore());
    formProcessing.value = processing;
};

const onFormSuccess = (message: string, modalToClose?: string, onSuccessAction?: () => void) => {
    if (modalToClose) {
        closeModal(modalToClose);
    }
    if (onSuccessAction) {
        onSuccessAction();
    }

    successAlert(message);
    router.visit(window.location.href, { replace: true });
};
const onFormError = (message: string, modalToClose?: string) => {
    if (modalToClose) {
        closeModal(modalToClose);
    }
    errorAlert(message);
};

const onFormFinish = (form: InertiaForm<any>) => {
    form.reset();
    toggleFormLoader(false);
};

function buildFormOptions(form: InertiaForm<any>, successMessage: string, errorMessage: string, modalToClose?: string, onSuccessAction?: () => void) {
    return {
        onStart: () => toggleFormLoader(true),
        onFinish: () => onFormFinish(form),
        onSuccess: () => onFormSuccess(successMessage, modalToClose, onSuccessAction),
        onError: () => onFormError(errorMessage, modalToClose),
    };
}

function mergeValidationSchema(schemaFields: Record<string, () => ZodObject<any, any>>) {
    return (keys: string[], initialSchema: ZodObject<any, any>): ZodObject<any, any> =>
        keys.reduce((schema, key) => schema.merge(schemaFields[key]()), initialSchema);
}

function validateSelectOption(val: SelectOption) {
    return val !== null && val?.value !== '';
}

export { buildFormOptions, clearFormErrors, mergeValidationSchema, onFormError, onFormFinish, onFormSuccess, toggleFormLoader, validateSelectOption };
