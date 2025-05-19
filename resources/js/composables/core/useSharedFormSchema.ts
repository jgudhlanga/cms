import { SelectOption } from '@/types/utils';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { z } from 'zod';

export const useSharedFormSchema = () => {
    const firstNameSchema = () =>
        z.object({
            first_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.first_name') })),
        });
    const lastNameSchema = () =>
        z.object({
            last_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.last_name') })),
        });
    const emailSchema = () =>
        z.object({
            email: z
                .string()
                .nonempty(trans('trans.enter_required_field', { field: trans('trans.email_address') }))
                .email(),
        });
    const titleSchema = () =>
        z.object({
            title: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.title', 1) })),
        });
    const nameSchema = () =>
        z.object({
            name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.name', 1) })),
        });
    const codeSchema = () =>
        z.object({
            code: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.code', 1) })),
        });
    const passwordSchema = () =>
        z.object({
            password: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.password') })),
        });
    const passwordConfirmationSchema = () =>
        z.object({
            password_confirmation: z.string().nonempty(trans('trans.confirm_password_description')),
        });
    const contactNameSchema = () =>
        z.object({
            contact_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.representative_name') })),
        });
    const emailAddressSchema = () =>
        z.object({
            email_address: z
                .string()
                .nonempty(trans('trans.enter_required_field', { field: trans('trans.email_address') }))
                .email(),
        });
    const phoneNumberSchema = () =>
        z.object({
            phone_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.phone_number') })),
        });
    const addressOneSchema = () =>
        z.object({
            address_1: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.street_number') })),
        });
    const addressTwoSchema = () =>
        z.object({
            address_2: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.street_name') })),
        });
    const addressThreeSchema = () =>
        z.object({
            address_3: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.city_town_suburb') })),
        });

    /* Dropdown Schema */
    const provinceSchema = () =>
        z.object({
            province: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.province', 1) }),
            }),
        });
    const titleIdSchema = () =>
        z.object({
            title: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.title', 1) }),
            }),
        });
    const genderSchema = () =>
        z.object({
            gender: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.gender', 1) }),
            }),
        });

    function validateSelectOption(val: SelectOption) {
        return val !== null && val?.value !== '';
    }

    return {
        addressOneSchema,
        addressThreeSchema,
        addressTwoSchema,
        codeSchema,
        contactNameSchema,
        emailAddressSchema,
        nameSchema,
        passwordSchema,
        phoneNumberSchema,
        provinceSchema,
        titleSchema,
        titleIdSchema,
        genderSchema,
        emailSchema,
        firstNameSchema,
        lastNameSchema,
        passwordConfirmationSchema,
    };
};
