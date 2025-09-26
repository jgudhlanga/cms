<script setup lang="ts">
// UI components
import { computed, onMounted, ref, watch } from 'vue';

// Page sections
import ContactDetails from '@/components/students/update/ContactDetails.vue';
import NextOfKinDetails from '@/components/students/update/NextOfKinDetails.vue';
import PersonalDetails from '@/components/students/update/PersonalDetails.vue';
import Programs from '@/components/students/update/Programs.vue';

// Composable
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useStudentPortal } from '@/composables/students/useStudentPortal';

// Store & types
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { AuthObject } from '@/types/data-pagination';
import { CreateApplicationParams } from '@/types/portal';

// Utilities
import { BaseButton } from '@/components/core/button';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { ButtonSize } from '@/enums/buttons';
import { errorAlert } from '@/lib/alerts';
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

// Composable
const { idTypes, listIdTypes } = useIdTypes();
const { applicationFormSchema, saveApplication } = useStudentPortal();
const { listLevelRequirements, levelRequirements } = useDepartmentLevels();
const { isNativeCitizen, isItTrue } = useUtils();
const { validateMainSubjects, validateOtherSubjects, updateCreateForm } = useApplicationFormHelper();

// Store
const storeRefs = storeToRefs(useCreateApplicationFormStore());

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

watch(
    () => storeRefs.level.value?.value,
    (levelId) => {
        if (levelId) listLevelRequirements(levelId.toString());
    },
);

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
    updateCreateForm(form);
    try {
        isValidating.value = true;
        await applicationFormSchema(isNativeCitizen(storeRefs.idType?.value?.label ?? '')).parseAsync(form);
        if (isItTrue(levelRequirements.value?.attributes?.isOLevelRequired)) {
            const mainErrors = validateMainSubjects();
            if (mainErrors && mainErrors.length > 0) {
                errorAlert(mainErrors.join('\n'));
                return;
            }
            const otherErrors = validateOtherSubjects();
            if (otherErrors && otherErrors.length > 0) {
                errorAlert(otherErrors.join('\n'));
                return;
            }
        }

        if (isItTrue(Number(String(levelRequirements.value?.attributes?.requiredLevelId)) > 0)) {
            if (!isItTrue(storeRefs.required_level_completed?.value)) {
                errorAlert(trans('trans.acknowledge_level_completed'));
                return;
            }
        }
        if (isItTrue(levelRequirements.value?.attributes?.onlyReadWriteRequired)) {
            if (!isItTrue(storeRefs.read_write_acknowledged?.value)) {
                errorAlert(trans('trans.acknowledge_read_write'));
                return;
            }
        }

        saveApplication(form);
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
            <div v-else class="flex w-full flex-col md:mx-auto md:w-7/8">
                <PersonalDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <ContactDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <NextOfKinDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <Programs :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <div class="flex items-center justify-center mb-10">
                    <BaseButton class="w-full md:w-[200px]" :size="ButtonSize.xl">
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
</template>
