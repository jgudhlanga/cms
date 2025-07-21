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
import { BaseButton, IconButton } from '@/components/core/button';
import AppLogo from '@/components/core/image/AppLogo.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import Heading from '@/components/core/util/Heading.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { useApplicationFormHelper } from '@/composables/students/useApplicationFormHelper';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import { useForm } from '@inertiajs/vue3';
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
const { listLevelRequirements } = useDepartmentLevels();
const { isNativeCitizen, isItTrue } = useUtils();
const { validateMainSubjects, validateOtherSubjects, updateForm } = useApplicationFormHelper();

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
    department: null,
    department_id: null,
    course: null,
    course_id: null,
    level: null,
    level_id: null,
    required_level_completed: null,
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
    storeRefs.middle_name.value = attrs?.middleName ?? '';
    storeRefs.last_name.value = attrs?.lastname;
    storeRefs.email.value = attrs?.email ?? '';
    if (!storeRefs.idType.value) {
        storeRefs.idType.value = {
            label: defaultIdType.value?.attributes?.name ?? '',
            value: Number(defaultIdType.value?.id) || '',
        };
    }
};

// Sync Pinia refs into form

onMounted(async () => {
    await listIdTypes();
    populateInitialForm();
});

const isValidating = ref(false);

const save = async () => {
    updateForm(form);
    try {
        isValidating.value = true;
        await applicationFormSchema(isNativeCitizen(storeRefs.idType?.value?.label ?? '')).parseAsync(form);
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
</script>
<template>
    <nav class="fixed top-0 right-0 left-0 z-50 w-full bg-white px-10 shadow">
        <div class="flex w-full items-center justify-between space-x-5 py-3 md:mx-auto md:w-6/8">
            <div class="flex size-8 items-center justify-start rounded-sm border">
                <AppLogo class="shrink-0" />
            </div>
            <Heading :title="user.attributes?.name" />
            <div class="flex">
                <TextLink :href="route('logout')" method="post" as="button" classes="text-destructive block text-sm uppercase">
                    <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" class="size-3" />
                </TextLink>
            </div>
        </div>
    </nav>
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col px-10 md:p-0">
            <div class="flex w-full flex-col md:mx-auto md:w-6/8">
                <div class="flex flex-col items-center justify-center">
                    <p class="text-muted-foreground text-sm font-semibold">-- {{ $t('trans.application_form_description') }} --</p>
                    <CustomSeparator classes="w-full md:w-1/2 mt-4" />
                </div>
                <PersonalDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <ContactDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <NextOfKinDetails :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <Programs :form="form" />
                <CustomSeparator classes="h-1 my-5" />
                <div class="flex items-center justify-center">
                    <BaseButton class="mb-10 w-[200px]" :size="ButtonSize.xl">
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
</template>
