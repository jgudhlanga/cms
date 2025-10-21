import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, successAlert } from '@/lib/alerts';
import { mergeValidationSchema } from '@/lib/forms';
import { emailUniqueSchema, idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { ClassSizeSlot, Enrolment } from '@/types/enrolments';
import { InertiaForm } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useEnrolments = () => {
    const { moreActionButton, textLink } = useDataTables();

    const enrolmentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const studentName = row.original?.attributes?.studentName;
                    const studentId = String(row.original?.attributes?.studentId);
                    return textLink(route('enrolments.show-profile', {student: studentId}), studentName ?? '');
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return moreActionButton(false, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const cashApplicationFormSchema = (isNativeCitizen: boolean) => {
        const personal = [
            'firstNameSchema',
            'lastNameSchema',
            'genderSchema',
            'maritalStatusSchema',
            'dobSchema',
            'idTypeSchema',
            'addressOneSchema',
            'addressTwoSchema',
            'addressThreeSchema',
            'emailSchema',
            'phoneNumberSchema',
            'nextOfKinPhoneNumberSchema',
            'nextOfKinAddressOneSchema',
            'nextOfKinAddressTwoSchema',
            'nextOfKinAddressThreeSchema',
            'relationshipSchema',
            'nextOfKinNameSchema',
            'levelSchema',
            'courseSchema',
            'departmentSchema',
            'modeOfStudySchema',
            'paymentReferenceSchema',
            'paymentDateSchema',
            'proofOfPaymentSchema',
        ];
        let personalDetails = null;
        if (isNativeCitizen) {
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(idNumberUniqueSchema('api/v1/validations/check?key=student_national_id&value='))
                    .merge(emailUniqueSchema('api/v1/validations/check?key=user_email&value=')),
            );
        } else {
            personal.push('countrySchema');
            personalDetails = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(passportNumberUniqueSchema('api/v1/validations/check?key=student_passport_number&value='))
                    .merge(emailUniqueSchema('api/v1/validations/check?key=user_email&value=')),
            );
        }
        return personalDetails;
    };

    const createEnrolment = (form: InertiaForm<any>) => {
        try {
            form.post(route('students.store'), {
                onSuccess: () => {
                    const store = useCreateApplicationFormStore();
                    store.$reset();
                    store.$dispose();
                    successAlert('Application successfully created');
                },
                onError: (errors: any) => {
                    if (Object.keys(errors).length) {
                        const allErrors = Object.values(errors).join('\n');
                        errorAlert(allErrors);
                    } else {
                        errorAlert('An unexpected error happened, application could not be created');
                    }
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const  allocateClassSlots = (classSize: number, disabledCount: number, femaleCount: number, maleCount: number): ClassSizeSlot =>  {
        // Step 1: Assign disabled share
        const remainingSlots = Math.max(classSize - disabledCount, 0);

        // Step 2: Split equally
        let femaleSlots = Math.floor(remainingSlots / 2);
        let maleSlots = Math.floor(remainingSlots / 2);
        const remainder = remainingSlots % 2;

        // Step 3: Handle odd remainder
        if (remainder === 1) {
            if (femaleCount > maleCount) {
                femaleSlots += 1;
            } else {
                maleSlots += 1;
            }
        }

        // Step 4: Adjust for population limits
        if (femaleCount < femaleSlots) {
            const transfer = femaleSlots - femaleCount;
            femaleSlots = femaleCount;
            maleSlots += transfer;
        }

        if (maleCount < maleSlots) {
            const transfer = maleSlots - maleCount;
            maleSlots = maleCount;
            femaleSlots += transfer;
        }

        // Ensure total doesn’t exceed class size
        const total = disabledCount + femaleSlots + maleSlots;
        if (total > classSize) {
            const excess = total - classSize;
            if (femaleSlots > maleSlots) {
                femaleSlots -= excess;
            } else {
                maleSlots -= excess;
            }
        }

        return {
            disabled: disabledCount,
            females: femaleSlots,
            males: maleSlots,
        };
    }


    return {
        enrolmentColumns,
        cashApplicationFormSchema,
        createEnrolment,
        allocateClassSlots,
    };
};
