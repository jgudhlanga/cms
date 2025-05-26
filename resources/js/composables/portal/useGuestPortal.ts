import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { useCreateUserFormStore } from '@/store/portal/useCreateUserFormStore';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export function useGuestPortal() {
    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.application', 1) });
    const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.application', 1) });
    const createApplication = (form: InertiaForm<any>) => {
        try {
            mergeValidationSchema(schemaFields)(
                ['titleIdSchema', 'lastNameSchema', 'genderSchema', 'emailSchema', 'passwordSchema', 'passwordConfirmationSchema'],
                schemaFields['firstNameSchema'](),
            ).parse(form);
            form.post(route('portal.store'), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useCreateUserFormStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return { schemaFields, createApplication };
}
