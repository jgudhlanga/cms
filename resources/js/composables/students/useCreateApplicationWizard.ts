import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { useUtils } from '@/composables/core/useUtils';
import { scrollToFirstError } from '@/lib/scrollToFirstError';
import { mergeValidationSchema } from '@/lib/forms';
import { idNumberUniqueSchema, passportNumberUniqueSchema } from '@/lib/uniqueValidations';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, ref } from 'vue';
import { ZodError } from 'zod';
import type { ApplicationFormStep } from '@/components/portal/PortalApplicationStepper.vue';

export type { ApplicationFormStep };

const STEP_ORDER: ApplicationFormStep[] = ['personal', 'contact', 'next_of_kin', 'programme'];

export function useCreateApplicationWizard(
    form: InertiaForm<CreateApplicationParams>,
    studentId?: string | number | null,
) {
    const currentStep = ref<ApplicationFormStep>('personal');
    const stepErrorHint = ref<string | null>(null);
    const storeRefs = storeToRefs(useCreateApplicationFormStore());
    const { applicationFormSchema } = useStudentPortal();
    const { validateMainSubjects, validateOtherSubjects } = useApplicationFormHelper();
    const { isNativeCitizen, isItTrue } = useUtils();
    const schemaFields = useSharedFormSchema() as Record<string, () => import('zod').ZodObject<any, any>>;

    const currentStepIndex = computed(() => STEP_ORDER.indexOf(currentStep.value));
    const isFirstStep = computed(() => currentStepIndex.value === 0);
    const isLastStep = computed(() => currentStepIndex.value === STEP_ORDER.length - 1);

    const stepTitleKey = computed(() => {
        const keys: Record<ApplicationFormStep, string> = {
            personal: 'trans.personal_details',
            contact: 'trans.contact_details',
            next_of_kin: 'trans.next_of_kin',
            programme: 'trans.programs',
        };
        return keys[currentStep.value];
    });

    const stepDescriptionKey = computed(() => {
        const keys: Record<ApplicationFormStep, string> = {
            personal: 'trans.personal_details_description',
            contact: 'trans.contact_details_description',
            next_of_kin: 'trans.next_of_kin_description',
            programme: 'trans.programme_step_description',
        };
        return keys[currentStep.value];
    });

    const primaryActionLabel = computed(() =>
        isLastStep.value ? trans('trans.review_application') : trans('trans.continue'),
    );

    const buildPersonalSchema = () => {
        const base = [
            'firstNameSchema',
            'lastNameSchema',
            'genderSchema',
            'maritalStatusSchema',
            'dobSchema',
            'idTypeSchema',
        ];
        const currentIdQuery = studentId != null && studentId !== '' ? `current_id=${studentId}&` : '';
        if (isNativeCitizen(storeRefs.idType?.value?.label ?? '')) {
            return mergeValidationSchema(schemaFields)(
                base,
                schemaFields['titleSchema']().merge(
                    idNumberUniqueSchema(`api/v1/validations/check?${currentIdQuery}key=student_national_id&value=`),
                ),
            );
        }
        return mergeValidationSchema(schemaFields)(
            [...base, 'countrySchema'],
            schemaFields['titleSchema']().merge(
                passportNumberUniqueSchema(`api/v1/validations/check?${currentIdQuery}key=student_passport_number&value=`),
            ),
        );
    };

    const buildContactSchema = () =>
        mergeValidationSchema(schemaFields)(
            ['emailSchema', 'addressOneSchema', 'addressTwoSchema', 'addressThreeSchema'],
            schemaFields['phoneNumberSchema'](),
        );

    const buildNextOfKinSchema = () =>
        mergeValidationSchema(schemaFields)(
            [
                'nextOfKinPhoneNumberSchema',
                'relationshipSchema',
                'nextOfKinAddressOneSchema',
                'nextOfKinAddressTwoSchema',
                'nextOfKinAddressThreeSchema',
            ],
            schemaFields['nextOfKinNameSchema'](),
        );

    const buildProgrammeSchema = () =>
        mergeValidationSchema(schemaFields)(
            ['departmentSchema', 'levelSchema', 'courseSchema'],
            schemaFields['modeOfStudySchema'](),
        );

    const setZodErrors = (error: unknown) => {
        if (error instanceof ZodError) {
            const fieldErrors = error.flatten().fieldErrors;
            const formattedErrors: Record<string, string> = {};
            for (const [key, messages] of Object.entries(fieldErrors)) {
                if (messages && messages.length > 0) {
                    formattedErrors[key] = messages[0];
                }
            }
            form.setError(formattedErrors);
            return;
        }

        console.error(error);
    };

    const validatePersonalStep = async (): Promise<boolean> => {
        form.clearErrors();
        const inlineErrors: Record<string, string> = {};
        if (storeRefs.disability_status?.value === null || storeRefs.disability_status?.value === undefined) {
            inlineErrors.disability_status = trans('trans.enter_required_field', { field: trans('trans.disability') });
        }
        try {
            await buildPersonalSchema().parseAsync(form);
            form.clearErrors();
            if (Object.keys(inlineErrors).length > 0) {
                form.setError(inlineErrors);
                scrollToFirstError(inlineErrors);
                return false;
            }
            return true;
        } catch (error: any) {
            setZodErrors(error);
            scrollToFirstError(form.errors, Object.keys(inlineErrors));
            return false;
        }
    };

    const validateContactStep = async (): Promise<boolean> => {
        form.clearErrors();
        try {
            await buildContactSchema().parseAsync(form);
            form.clearErrors();
            return true;
        } catch (error: any) {
            setZodErrors(error);
            scrollToFirstError(form.errors);
            return false;
        }
    };

    const validateNextOfKinStep = async (): Promise<boolean> => {
        form.clearErrors();
        try {
            await buildNextOfKinSchema().parseAsync(form);
            form.clearErrors();
            return true;
        } catch (error: any) {
            setZodErrors(error);
            scrollToFirstError(form.errors);
            return false;
        }
    };

    const validateProgrammeStep = async (
        requirements: CourseRequirement | DepartmentLevelRequirement | null | undefined,
    ): Promise<boolean> => {
        form.clearErrors();
        const inlineErrors: Record<string, string> = {};
        try {
            await buildProgrammeSchema().parseAsync(form);
        } catch (error: any) {
            setZodErrors(error);
            scrollToFirstError(form.errors);
            return false;
        }

        if (isItTrue(requirements?.attributes?.isOLevelRequired)) {
            const mainSubjectsCount = Number(String(requirements?.attributes?.mainSubjectsCount ?? '0'));
            const mainErrors = validateMainSubjects(mainSubjectsCount);
            if (mainErrors?.length) {
                inlineErrors.o_level = mainErrors[0];
            }
            const otherSubjectCount = Number(String(requirements?.attributes?.otherSubjectsCount ?? '0'));
            const otherErrors = validateOtherSubjects(otherSubjectCount);
            if (otherErrors?.length && !inlineErrors.o_level) {
                inlineErrors.o_level = otherErrors[0];
            }
        }
        if (isItTrue(Number(String(requirements?.attributes?.requiredLevelId)) > 0)) {
            if (!isItTrue(storeRefs.required_level_completed?.value)) {
                inlineErrors.required_level_completed = trans('trans.acknowledge_level_completed');
            }
        }
        if (isItTrue(requirements?.attributes?.onlyReadWriteRequired)) {
            if (!isItTrue(storeRefs.read_write_acknowledged?.value)) {
                inlineErrors.read_write_acknowledged = trans('trans.acknowledge_read_write');
            }
        }

        if (Object.keys(inlineErrors).length > 0) {
            form.setError(inlineErrors);
            scrollToFirstError(inlineErrors);
            return false;
        }

        form.clearErrors();
        return true;
    };

    const validateFullForm = async (
        requirements: CourseRequirement | DepartmentLevelRequirement | null | undefined,
    ): Promise<boolean> => {
        const inlineErrors: Record<string, string> = {};
        if (storeRefs.disability_status?.value === null || storeRefs.disability_status?.value === undefined) {
            inlineErrors.disability_status = trans('trans.enter_required_field', { field: trans('trans.disability') });
        }
        try {
            await applicationFormSchema(isNativeCitizen(storeRefs.idType?.value?.label ?? ''), studentId).parseAsync(form);
        } catch (error: any) {
            setZodErrors(error);
            scrollToFirstError(form.errors, Object.keys(inlineErrors));
            return false;
        }

        if (isItTrue(requirements?.attributes?.isOLevelRequired)) {
            const mainSubjectsCount = Number(String(requirements?.attributes?.mainSubjectsCount ?? '0'));
            const mainErrors = validateMainSubjects(mainSubjectsCount);
            if (mainErrors?.length) {
                inlineErrors.o_level = mainErrors[0];
            }
            const otherSubjectCount = Number(String(requirements?.attributes?.otherSubjectsCount ?? '0'));
            const otherErrors = validateOtherSubjects(otherSubjectCount);
            if (otherErrors?.length && !inlineErrors.o_level) {
                inlineErrors.o_level = otherErrors[0];
            }
        }
        if (isItTrue(Number(String(requirements?.attributes?.requiredLevelId)) > 0)) {
            if (!isItTrue(storeRefs.required_level_completed?.value)) {
                inlineErrors.required_level_completed = trans('trans.acknowledge_level_completed');
            }
        }
        if (isItTrue(requirements?.attributes?.onlyReadWriteRequired)) {
            if (!isItTrue(storeRefs.read_write_acknowledged?.value)) {
                inlineErrors.read_write_acknowledged = trans('trans.acknowledge_read_write');
            }
        }

        if (Object.keys(inlineErrors).length > 0) {
            form.setError(inlineErrors);
            scrollToFirstError(inlineErrors);
            return false;
        }

        form.clearErrors();
        return true;
    };

    const validateCurrentStep = async (
        requirements?: CourseRequirement | DepartmentLevelRequirement | null,
    ): Promise<boolean> => {
        stepErrorHint.value = null;
        switch (currentStep.value) {
            case 'personal':
                return validatePersonalStep();
            case 'contact':
                return validateContactStep();
            case 'next_of_kin':
                return validateNextOfKinStep();
            case 'programme':
                return validateProgrammeStep(requirements);
            default:
                return true;
        }
    };

    const setStep = (step: ApplicationFormStep) => {
        currentStep.value = step;
        form.clearErrors();
        stepErrorHint.value = null;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const goToStep = (step: ApplicationFormStep) => {
        const targetIndex = STEP_ORDER.indexOf(step);
        if (targetIndex === -1 || targetIndex >= currentStepIndex.value) {
            return;
        }
        setStep(step);
    };

    const goBack = () => {
        if (isFirstStep.value) {
            return;
        }
        goToStep(STEP_ORDER[currentStepIndex.value - 1]);
    };

    const goNext = async (requirements?: CourseRequirement | DepartmentLevelRequirement | null): Promise<boolean> => {
        const valid = await validateCurrentStep(requirements);
        if (!valid) {
            const errorCount = Object.keys(form.errors).length;
            if (errorCount > 0) {
                stepErrorHint.value = trans('trans.portal_fix_fields_above', { count: String(errorCount) });
            }
            return false;
        }
        if (isLastStep.value) {
            return true;
        }
        setStep(STEP_ORDER[currentStepIndex.value + 1]);
        return true;
    };

    return {
        currentStep,
        currentStepIndex,
        isFirstStep,
        isLastStep,
        stepTitleKey,
        stepDescriptionKey,
        primaryActionLabel,
        stepErrorHint,
        goToStep,
        goBack,
        goNext,
        validateFullForm,
        validateCurrentStep,
    };
}
