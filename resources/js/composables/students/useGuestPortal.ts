import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { emailUniqueSchema } from '@/lib/uniqueValidations';
import { useCreateUserFormStore } from '@/store/portal/useCreateUserFormStore';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { ZodObject } from 'zod';

export function useGuestPortal() {
    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    const successMessage = () => 'Account created. Continue with your application.';
    const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.application', 1) });

    const formSchema = () => {
        const personal = ['lastNameSchema', 'passwordSchema', 'passwordConfirmationSchema'];
        return mergeValidationSchema(schemaFields)(
            personal,
            schemaFields['firstNameSchema']().merge(emailUniqueSchema('api/v1/validations/check?key=user_email&value=')),
        );
    };

    const isValidating = ref(false);
    const createPortalUser = async (form: InertiaForm<any>, registrationPath: 'zimbabwean' | 'international') => {
        try {
            isValidating.value = true;
            form.registration_path = registrationPath;
            await formSchema().parseAsync(form);
            form.post(
                route('portal.store'),
                buildFormOptions(form, successMessage(), errorMessage(), undefined, () => {
                    useCreateUserFormStore().$reset();
                }),
            );
        } catch (error: any) {
            form.setError(error.format());
        } finally {
            isValidating.value = false;
        }
    };

    return { schemaFields, createPortalUser, isValidating };
}
