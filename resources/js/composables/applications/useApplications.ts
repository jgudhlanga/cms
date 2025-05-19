import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { useApplicationFormStore } from '@/store/applications/useApplicationFormStore';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export function useApplications() {
    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.application', 1) });
    const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.application', 1) });
    const createApplication = (form: InertiaForm<any>) => {
        try {
            mergeValidationSchema(schemaFields)(
                ['titleIdSchema', 'lastNameSchema', 'genderSchema', 'emailSchema', 'passwordSchema'],
                schemaFields['firstNameSchema'](),
            ).parse(form);
            form.post(route('schemes.update'), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useApplicationFormStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return { schemaFields, createApplication };
}
