import { validateSelectOption } from '@/lib/forms';
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
    const idNumberSchema = () =>
        z.object({
            id_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.id_number') })),
        });
    const passportNumberSchema = () =>
        z.object({
            passport_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.passport_number') })),
        });
    const emailSchema = () =>
        z.object({
            email: z
                .string()
                .nonempty(trans('trans.enter_required_field', { field: trans('trans.email_address') }))
                .email(),
        });
    const nameSchema = () =>
        z.object({
            name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.name', 1) })),
        });
    const schoolSchema = () =>
        z.object({
            school: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.school', 1) })),
        });
    const placeSchema = () =>
        z.object({
            place: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.place', 1) })),
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
    const employeeNumberSchema = () =>
        z.object({
            employee_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.employee_number') })),
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
            address_1: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_1') })),
        });
    const addressTwoSchema = () =>
        z.object({
            address_2: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_2') })),
        });
    const addressThreeSchema = () =>
        z.object({
            address_3: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_3') })),
        });

    const nextOfKinNameSchema = () =>
        z.object({
            next_of_kin_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.name', 1) })),
        });

    const nextOfKinPhoneNumberSchema = () =>
        z.object({
            next_of_kin_phone_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.phone_number') })),
        });
    const nextOfKinAddressOneSchema = () =>
        z.object({
            next_of_kin_address_1: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_1') })),
        });
    const nextOfKinAddressTwoSchema = () =>
        z.object({
            next_of_kin_address_2: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_2') })),
        });
    const nextOfKinAddressThreeSchema = () =>
        z.object({
            next_of_kin_address_3: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.address_3') })),
        });
    const dobSchema = () =>
        z.object({
            date_of_birth: z
                .union([z.string(), z.date()])
                .transform((val) => (typeof val === 'string' ? val : val.toISOString().split('T')[0]))
                .refine((val) => !isNaN(Date.parse(val)), {
                    message: trans('trans.date_must_be_valid', { field: trans('trans.date_of_birth') }),
                })
                .refine(
                    (val) => {
                        const dob = new Date(val);
                        const today = new Date();
                        const age = today.getFullYear() - dob.getFullYear();
                        const hasHadBirthdayThisYear =
                            today.getMonth() > dob.getMonth() || (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());

                        return age > 16 || (age === 16 && hasHadBirthdayThisYear);
                    },
                    {
                        message: trans('trans.student_minimum_age_required', { age: '16' }),
                    },
                ),
        });

    const titleLabelSchema = () =>
        z.object({
            title: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.title', 1) })),
        });

    /* Dropdown Schema */
    const idTypeSchema = () =>
        z.object({
            province: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.id_type', 1) }),
            }),
        });
    const provinceSchema = () =>
        z.object({
            province: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.province', 1) }),
            }),
        });
    const relationshipSchema = () =>
        z.object({
            relationship: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.relationship', 1) }),
            }),
        });
    const employmentTypeSchema = () =>
        z.object({
            employmentType: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.employment_type', 1) }),
            }),
        });
    const titleSchema = () =>
        z.object({
            title: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.title', 1) }),
            }),
        });
    const maritalStatusSchema = () =>
        z.object({
            maritalStatus: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.marital_status', 1) }),
            }),
        });
    const genderSchema = () =>
        z.object({
            gender: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.gender', 1) }),
            }),
        });
    const departmentSchema = () =>
        z.object({
            department: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.department', 1) }),
            }),
        });
    const levelSchema = () =>
        z.object({
            level: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.level', 1) }),
            }),
        });
    const courseSchema = () =>
        z.object({
            course: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.course', 1) }),
            }),
        });
    const countrySchema = () =>
        z.object({
            country: z.any().refine((val) => validateSelectOption(val), {
                message: trans('trans.select_valid_field', { field: trans_choice('trans.country', 1) }),
            }),
        });

    return {
        addressOneSchema,
        idTypeSchema,
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
        maritalStatusSchema,
        genderSchema,
        emailSchema,
        firstNameSchema,
        lastNameSchema,
        passwordConfirmationSchema,
        dobSchema,
        nextOfKinNameSchema,
        nextOfKinPhoneNumberSchema,
        nextOfKinAddressOneSchema,
        nextOfKinAddressTwoSchema,
        nextOfKinAddressThreeSchema,
        relationshipSchema,
        departmentSchema,
        levelSchema,
        courseSchema,
        idNumberSchema,
        passportNumberSchema,
        countrySchema,
        schoolSchema,
        placeSchema,
        employmentTypeSchema,
        employeeNumberSchema,
        titleLabelSchema,
    };
};
