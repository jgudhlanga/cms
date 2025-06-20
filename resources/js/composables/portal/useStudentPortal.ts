import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { Auth, PageProps } from '@/types';
import { Step } from '@/types/forms';
import { AddressType } from '@/types/settings';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export function useStudentPortal() {
    const { moreActionButton, onDelete, onForceDelete, onRestore, textLink } = useDataTables();

    const applicationsTable = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AddressType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.address_type', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () => {
                            }
                        },
                        {
                            key: 'view',
                            action: () => {
                            }
                        },
                        {
                            key: 'archive',
                            action: () => {
                            }
                        },
                        {
                            key: 'restore',
                            action: () => {
                            }
                        },
                        {
                            key: 'delete',
                            action: () => {
                            }
                        }
                    ]);
                }
            }
        ];
    };

    const steps: Step[] = [
        { step: 1, title: trans('trans.personal_details'), description: 'trans.personal_details_description' },
        { step: 2, title: trans('trans.contact_details'), description: 'trans.contact_details_description' },
        { step: 3, title: trans('trans.next_of_kin'), description: 'trans.next_of_kin_description' },
        { step: 4, title: trans('trans.programs'), description: 'trans.program_description' },
        { step: 5, title: trans('trans.confirmation'), description: 'trans.confirmation_description' }
    ];

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    const applicationFormSchema = (isNativeCitizen: boolean) => {
        const personal = ['firstNameSchema', 'lastNameSchema', 'genderSchema', 'maritalStatusSchema'];
        if (isNativeCitizen) {
            personal.push('idNumberSchema');
        } else {
            personal.push('passportNumberSchema');
            personal.push('countrySchema');
        }
        const personalDetails = mergeValidationSchema(schemaFields)(personal, schemaFields['titleSchema']());
        const contacts = mergeValidationSchema(schemaFields)(
            ['addressOneSchema', 'addressTwoSchema', 'addressThreeSchema', 'emailSchema'],
            schemaFields['phoneNumberSchema']()
        );
        const nextOfKin = mergeValidationSchema(schemaFields)(
            [
                'nextOfKinPhoneNumberSchema',
                'nextOfKinAddressOneSchema',
                'nextOfKinAddressTwoSchema',
                'nextOfKinAddressThreeSchema',
                'relationshipSchema'
            ],
            schemaFields['nextOfKinNameSchema']()
        );
        const programs = mergeValidationSchema(schemaFields)(['levelSchema', 'courseSchema'], schemaFields['departmentSchema']());
        return [personalDetails, contacts, nextOfKin, programs];
    };

    const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.application', 1) });
    const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.application', 1) });
    const saveApplication = (form: InertiaForm<any>) => {
        const { props } = usePage<PageProps>();
        const { user } = props?.auth;
        try {
            form.post(route('portal.store-application', getIdParams(user.id ?? '')), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useCreateApplicationFormStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    return { applicationsTable, steps, applicationFormSchema, saveApplication };
}
