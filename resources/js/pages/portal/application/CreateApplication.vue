<script setup lang="ts">
// UI components
import { computed, onMounted, ref } from 'vue';

// Page sections
import ContactDetails from '@/components/students/update/ContactDetails.vue';
import NextOfKinDetails from '@/components/students/update/NextOfKinDetails.vue';
import PersonalDetails from '@/components/students/update/PersonalDetails.vue';
import Programs from '@/components/students/update/Programs.vue';

// Composable
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';

// Store & types
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';

// Utilities
import { BaseButton } from '@/components/core/button';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { ButtonSize } from '@/enums/buttons';
import { errorAlert } from '@/lib/alerts';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';

// Props
interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const requirements = ref<CourseRequirement | DepartmentLevelRequirement | null | undefined>(null);
// Composable
const { idTypes, listIdTypes } = useIdTypes();
const { applicationFormSchema } = useStudentPortal();
const { isNativeCitizen, isItTrue } = useUtils();
const { validateMainSubjects, validateOtherSubjects, updateCreateForm } = useApplicationFormHelper();
const { navigateTo } = useUtils();
const store = useCreateApplicationFormStore();
const storeRefs = storeToRefs(store);
const getRequirements = () => {
    requirements.value =
        storeRefs.courseRequirements?.value && Number(String(storeRefs.courseRequirements?.value?.id)) > 0
            ? storeRefs.courseRequirements?.value
            : storeRefs.levelRequirements?.value;
};
// Store

// Form
const form = useForm<CreateApplicationParams>({
    email: '',
    first_name: '',
    gender: null,
    gender_id: null,
    last_name: null,
    middle_name: null,
    title: null,
    title_id: null,
    address_1: '',
    address_2: '',
    address_3: '',
    address_4: '',
    alt_phone_number: '',
    country: null,
    country_id: null,
    date_of_birth: '',
    id_number: '',
    id_type_id: null,
    idType: null,
    maritalStatus: null,
    marital_status_id: null,
    next_of_kin_address_1: '',
    next_of_kin_address_2: '',
    next_of_kin_address_3: '',
    next_of_kin_address_4: '',
    next_of_kin_name: '',
    next_of_kin_phone_number: '',
    passport_number: '',
    phone_number: '',
    relationship: null,
    relationship_id: null,
    study_permit_number: '',
    modeOfStudy: null,
    mode_of_study_id: null,
    department: null,
    department_id: null,
    course: null,
    course_id: null,
    disability_status: null,
    level: null,
    level_id: null,
    required_level_completed: null,
    required_level_upload: null,
    read_write_acknowledged: null,
    o_level_subject_ids: null,
    o_level_years: null,
    o_level_sittings: null,
    o_level_other_subject_ids: null,
    o_level_other_grade_ids: null,
    o_level_other_years: null,
    o_level_other_sittings: null,
});

const defaultIdType = computed(() => {
    return idTypes.value.find((type) => isItTrue(type.attributes?.isDefault)) ?? null;
});

// Populate from user
const populateInitialForm = () => {
    const attrs = user.attributes;
    storeRefs.first_name.value = attrs?.firstname;
    storeRefs.last_name.value = attrs?.lastname;
    storeRefs.email.value = attrs?.email ?? '';
    if (!storeRefs.idType.value) {
        storeRefs.idType.value = {
            label: defaultIdType.value?.attributes?.name ?? '',
            value: Number(defaultIdType.value?.id) || '',
        };
    }
};

onMounted(async () => {
    await listIdTypes();
    populateInitialForm();
});

const isValidating = ref(false);

const save = async () => {
    getRequirements();
    updateCreateForm(form);
    try {
        isValidating.value = true;
        await applicationFormSchema(isNativeCitizen(storeRefs.idType?.value?.label ?? '')).parseAsync(form);
        if (storeRefs.disability_status?.value === null || storeRefs.disability_status?.value === undefined) {
            errorAlert('Please choose your disability status');
            return;
        }
        if (isItTrue(requirements.value?.attributes?.isOLevelRequired)) {
            const mainSubjectsCount = Number(String(requirements.value?.attributes?.mainSubjectsCount ?? '0'));
            const mainErrors = validateMainSubjects(mainSubjectsCount);
            if (mainErrors && mainErrors.length > 0) {
                errorAlert(mainErrors.join('\n'));
                return;
            }
            const otherSubjectCount = Number(String(requirements.value?.attributes?.otherSubjectsCount ?? '0'));
            const otherErrors = validateOtherSubjects(otherSubjectCount);
            if (otherErrors && otherErrors.length > 0) {
                errorAlert(otherErrors.join('\n'));
                return;
            }
        }
        if (isItTrue(Number(String(requirements.value?.attributes?.requiredLevelId)) > 0)) {
            if (!isItTrue(storeRefs.required_level_completed?.value)) {
                errorAlert(trans('trans.acknowledge_level_completed'));
                return;
            }
        }
        if (isItTrue(requirements.value?.attributes?.onlyReadWriteRequired)) {
            if (!isItTrue(storeRefs.read_write_acknowledged?.value)) {
                errorAlert(trans('trans.acknowledge_read_write'));
                return;
            }
        }
        navigateTo(route('portal.application.confirm'));
    } catch (error: any) {
        if (error?.format) {
            form.setError(error.format());
        } else {
            console.error(error);
        }
    } finally {
        isValidating.value = false;
    }
};
const maintenanceMode = isItTrue(import.meta.env.VITE_MAINTENANCE_MODE);
</script>
<template>
    <StudentPageHeader />
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col bg-white px-10 md:p-0">
            <ComingSoonAnimated v-if="maintenanceMode" />
            <div v-else class="flex w-full flex-col space-y-6 md:mx-auto md:w-7/8">
                <PersonalDetails :form="form" />
                <ContactDetails :form="form" />
                <NextOfKinDetails :form="form" />
                <Programs :form="form" />
                <div class="mb-5 flex items-center justify-center">
                    <BaseButton class="w-full md:w-[200px]" :size="ButtonSize.xl">
                        {{ $t('trans.continue') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
</template>
