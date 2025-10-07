import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { mergeValidationSchema } from '@/lib/forms';
import { idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import { Student, StudentProgram } from '@/types/students';
import { trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useEnrolments = () => {
    const { moreActionButton, textLink } = useDataTables();

    const enrolmentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: StudentProgram } }) => {
                    const studentName = row.original?.relationships?.student?.relationships?.user?.attributes?.name;
                    return textLink(route('portal.programs'), studentName ?? '');
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Student } }) => {
                    console.log(row);
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
        ];
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
        return personalDetails;
    };

    return {
        enrolmentColumns,
        cashApplicationFormSchema,
    };
};
