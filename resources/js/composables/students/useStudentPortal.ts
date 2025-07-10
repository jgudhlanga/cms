import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { Step } from '@/types/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export function useStudentPortal() {
    const steps: Step[] = [
        { step: 1, title: trans('trans.personal_details'), description: 'trans.personal_details_description' },
        { step: 2, title: trans('trans.contact_details'), description: 'trans.contact_details_description' },
        { step: 3, title: trans('trans.next_of_kin'), description: 'trans.next_of_kin_description' },
        { step: 4, title: trans('trans.programs'), description: 'trans.program_description' },
        { step: 5, title: trans('trans.confirmation'), description: 'trans.confirmation_description' },
    ];

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const applicationFormSchema = (isNativeCitizen: boolean) => {
        const personal = ['firstNameSchema', 'lastNameSchema', 'genderSchema', 'maritalStatusSchema', 'dobSchema', 'idTypeSchema'];
        let personalDetails = null;
        if (isNativeCitizen) {
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']().merge(idNumberUniqueSchema('api/v1/validations/check?key=student_national_id&value=')),
            );
        } else {
            personal.push('countrySchema');
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']().merge(passportNumberUniqueSchema('api/v1/validations/check?key=student_passport_number&value=')),
            );
        }
        const contacts = mergeValidationSchema(schemaFields)(
            ['addressOneSchema', 'addressTwoSchema', 'addressThreeSchema', 'emailSchema'],
            schemaFields['phoneNumberSchema'](),
        );
        const nextOfKin = mergeValidationSchema(schemaFields)(
            [
                'nextOfKinPhoneNumberSchema',
                'nextOfKinAddressOneSchema',
                'nextOfKinAddressTwoSchema',
                'nextOfKinAddressThreeSchema',
                'relationshipSchema',
            ],
            schemaFields['nextOfKinNameSchema'](),
        );
        const programs = mergeValidationSchema(schemaFields)(['levelSchema', 'courseSchema'], schemaFields['departmentSchema']());
        return [personalDetails, contacts, nextOfKin, programs];
    };

    const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.application', 1) });
    const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.application', 1) });
    const saveApplication = (form: InertiaForm<any>) => {
        try {
            form.post(route('portal.store-application'), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useCreateApplicationFormStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    return { steps, applicationFormSchema, saveApplication };
}
