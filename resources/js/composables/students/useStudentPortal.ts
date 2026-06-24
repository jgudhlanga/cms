import StudentAddresses from '@/components/students/tabs/StudentAddresses.vue';
import StudentBasicInfo from '@/components/students/tabs/StudentBasicInfo.vue';
import StudentContacts from '@/components/students/tabs/StudentContacts.vue';
import StudentNextOfKin from '@/components/students/tabs/StudentNextOfKin.vue';
import StudentSponsors from '@/components/students/tabs/StudentSponsors.vue';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import HttpService from '@/services/http.service';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Step } from '@/types/forms';
import { Student } from '@/types/students';
import { CustomTab, SelectOption } from '@/types/utils';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { ZodObject } from 'zod';

export function useStudentPortal() {
    const steps: Step[] = [
        { step: 1, title: trans('trans.personal'), description: 'trans.personal_details_description' },
        { step: 2, title: trans('trans.contact_details'), description: 'trans.contact_details_description' },
        { step: 3, title: trans('trans.next_of_kin'), description: 'trans.next_of_kin_description' },
        { step: 4, title: trans('trans.programs'), description: 'trans.program_description' },
        { step: 5, title: trans('trans.documents'), description: 'trans.documents_description' },
        { step: 6, title: trans('trans.confirmation'), description: 'trans.confirmation_description' },
    ];

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    const applicationFormSchema = (isNativeCitizen: boolean) => {
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
    const programFormSchema = () => {
        const validations = ['courseSchema', 'departmentSchema', 'modeOfStudySchema'];

        return mergeValidationSchema(schemaFields)(validations, schemaFields['levelSchema']());
    };

    const saveApplication = (form: InertiaForm<any>) => {
        try {
            form.post(route('portal.store-application'), {
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

    const updateApplication = (applicationId: string, form: InertiaForm<any>) => {
        try {
            form.put(route('portal.application.update', applicationId), {
                onSuccess: () => {
                    const store = useUpdateProgramFormStore();
                    store.$reset();
                    store.$dispose();
                    successAlert('Application successfully updated');
                },
                onError: (errors: any) => {
                    if (Object.keys(errors).length) {
                        const allErrors = Object.values(errors).join('\n');
                        errorAlert(allErrors);
                    } else {
                        errorAlert('An unexpected error happened, application could not be updated');
                    }
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const addProgram = (studentId: string, form: InertiaForm<any>) => {
        try {
            form.post(route('portal.program.store', studentId), {
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

    const studentTabs = (): CustomTab[] => {
        return [
            {
                transLabel: () => 'Personal',
                value: 'personal',
                component: h(StudentBasicInfo),
                icon: IconName.user,
            },
            {
                transLabel: () => trans_choice('trans.contact', 2),
                value: 'contacts',
                component: h(StudentContacts),
                icon: IconName.contact,
            },
            {
                transLabel: () => trans_choice('trans.address', 2),
                value: 'addresses',
                component: h(StudentAddresses),
                icon: IconName.address,
            },
            {
                transLabel: () => trans_choice('trans.sponsor', 2),
                value: 'sponsors',
                component: h(StudentSponsors),
                icon: IconName.wallet_cards,
            },
            {
                transLabel: () => trans('trans.next_of_kin'),
                value: 'next_of_kin',
                component: h(StudentNextOfKin),
                icon: IconName.open_link,
            },
        ];
    };

    const isLoading = ref(false);

    const getStudentData = async (url: string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.portal_info') }));
        } finally {
            isLoading.value = false;
        }
    };

    const allowed = hasAbility('manageOwnStudentPersonalDetails:students');
    const onOpenPersonalDetailsModal = (student?: Student) => {
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.student_personal_details, edit: student });
    };
    const updateStudentSchema = (isNativeCitizen: boolean, studentId: string) => {
        const personal = ['genderSchema', 'maritalStatusSchema', 'dobSchema', 'idTypeSchema'];
        let updateSchema = null;
        if (isNativeCitizen) {
            updateSchema = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']().merge(
                    idNumberUniqueSchema(`api/v1/validations/check?current_id=${studentId}&key=student_national_id&value=`),
                ),
            );
        } else {
            personal.push('countrySchema');
            updateSchema = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']().merge(
                    passportNumberUniqueSchema(`api/v1/validations/check?current_id=${studentId}&key=student_passport_number&value='`),
                ),
            );
        }
        return updateSchema;
    };

    const getName = () => trans_choice('trans.student', 1);
    const updateSuccessMessage = () => trans('trans.item_saved', { item: getName() });
    const updateErrorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const updateStudent = async (studentId: string, form: InertiaForm<any>) => {
        try {
            form.put(
                route('students.update', studentId),
                buildFormOptions(form, updateSuccessMessage(), updateErrorMessage(), APP_MODULE_KEYS.student_personal_details),
            );
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const getMainSittingYear = (yearData: Record<string, string>): string | null => {
        if (yearData) {
            const years: any = Object.values(yearData).filter(Boolean);
            if (years.length > 0) {
                // Check if all values are the same
                const firstYear = years[0];
                const allSame = years.every((year: string) => year == firstYear);
                if (allSame) {
                    return firstYear;
                }
            }
        }
        return null;
    };

    const getMainSitting = (sittingData: Record<string, SelectOption>): SelectOption | null => {
        if (sittingData) {
            const sittings = Object.values(sittingData).filter(Boolean);
            if (sittings.length > 0) {
                const firstSitting = sittings[0];
                const allSame = sittings.every((sitting) => sitting.label === firstSitting.label && sitting.value === firstSitting.value);
                if (allSame) {
                    return firstSitting;
                }
            }
        }
        return null;
    };

    const selectLevel = (levelId: string, intakePeriodId?: number | null, requiresIntakeSelection = false) => {
        const payload: Record<string, string | number> = {
            level_id: levelId,
        };

        if (intakePeriodId) {
            payload.intake_period_id = intakePeriodId;
        }

        router.post(route('portal.application.select-level'), payload, {
            onError: () => {
                if (requiresIntakeSelection && !intakePeriodId) {
                    return;
                }
            },
        });
    };
    return {
        steps,
        applicationFormSchema,
        saveApplication,
        studentTabs,
        getStudentData,
        isLoading,
        onOpenPersonalDetailsModal,
        updateStudent,
        updateStudentSchema,
        getMainSittingYear,
        getMainSitting,
        updateApplication,
        programFormSchema,
        addProgram,
        selectLevel,
    };
}
