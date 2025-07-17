<script setup lang="ts">
// UI components
import { computed, onMounted, watch } from 'vue';

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
import AppLogo from '@/components/core/image/AppLogo.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import Heading from '@/components/core/util/Heading.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { ButtonSize } from '@/enums/buttons';
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
    o_level_subject_ids: null,
    required_level_completed: null,
    read_write_acknowledged: null,
});

watch(
    () => storeRefs.level.value?.value,
    (levelId) => {
        if (levelId) listLevelRequirements(levelId.toString());
    },
);

const save = async () => {
    updateForm();
    try {
        await applicationFormSchema(isNativeCitizen(storeRefs.idType?.value?.label ?? '')).parseAsync(form);
        saveApplication(form);
    } catch (error: any) {
        if (error?.format) {
            form.setError(error.format());
        } else {
            console.error(error); // Unexpected error
        }
    }
};

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
const updateForm = () => {
    Object.assign(form, {
        email: storeRefs.email.value,
        first_name: storeRefs.first_name.value,
        gender: storeRefs.gender.value,
        gender_id: storeRefs.gender.value?.value ?? '',
        last_name: storeRefs.last_name.value,
        middle_name: storeRefs.middle_name.value ?? '',
        title: storeRefs.title.value,
        title_id: storeRefs.title.value?.value ?? '',
        address_1: storeRefs.address_1.value,
        address_2: storeRefs.address_2.value,
        address_3: storeRefs.address_3.value,
        address_4: storeRefs.address_4.value,
        alt_phone_number: storeRefs.alt_phone_number?.value ?? '',
        country: storeRefs.country?.value,
        country_id: storeRefs.country?.value?.value ?? null,
        date_of_birth: storeRefs.date_of_birth.value ?? '',
        id_number: storeRefs.id_number?.value ?? '',
        id_type_id: storeRefs.idType.value?.value ?? '',
        idType: storeRefs.idType.value ?? '',
        maritalStatus: storeRefs.maritalStatus?.value,
        marital_status_id: storeRefs.maritalStatus?.value?.value ?? null,
        next_of_kin_address_1: storeRefs.next_of_kin_address_1.value ?? '',
        next_of_kin_address_2: storeRefs.next_of_kin_address_2.value ?? '',
        next_of_kin_address_3: storeRefs.next_of_kin_address_3.value ?? '',
        next_of_kin_address_4: storeRefs.next_of_kin_address_4.value ?? '',
        next_of_kin_name: storeRefs.next_of_kin_name.value ?? '',
        next_of_kin_phone_number: storeRefs.next_of_kin_phone_number.value ?? '',
        passport_number: storeRefs.passport_number?.value ?? '',
        phone_number: storeRefs.phone_number?.value ?? '',
        relationship: storeRefs.relationship.value,
        relationship_id: storeRefs.relationship.value?.value ?? null,
        study_permit_number: storeRefs.study_permit_number?.value ?? '',
        department: storeRefs.department.value,
        department_id: storeRefs.department.value?.value ?? null,
        course: storeRefs.course.value,
        course_id: storeRefs.course.value?.value ?? null,
        level: storeRefs.level.value,
        level_id: storeRefs.level.value?.value ?? null,
        o_level_subject_ids: storeRefs.o_level_subject_ids?.value ?? null,
        required_level_completed: storeRefs.required_level_completed?.value ?? null,
        read_write_acknowledged: storeRefs.read_write_acknowledged?.value ?? null,
    });
};

// Lifecycle
onMounted(async () => {
    await listIdTypes();
    populateInitialForm();
});
</script>
<template>
    <nav class="fixed top-0 right-0 left-0 z-50 w-full bg-white shadow px-10">
        <div class="flex w-full md:w-6/8 md:mx-auto items-center justify-between space-x-5 py-3">
            <div class="flex size-8 items-center justify-start rounded-sm border">
                <AppLogo class="shrink-0" />
            </div>
            <Heading :title="user.attributes?.name" />
            <div class="flex">
                <TextLink :href="route('logout')" method="post" as="button" classes="text-destructive block text-sm uppercase">
                    {{ $t('trans.logout') }}
                </TextLink>
            </div>
        </div>
    </nav>
    <form @submit.prevent="() => save()">
        <div class="mt-20 flex w-full flex-col px-10 md:p-0">
            <div class="md:mx-auto flex w-full md:w-6/8 flex-col">
                <div class="flex flex-col items-center justify-center">
                    <p class="text-destructive text-md">-- {{ $t('trans.application_form_description') }} --</p>
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
                    <BaseButton class="w-[200px]" :size="ButtonSize.xl">
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </form>
</template>
