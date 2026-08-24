<script setup lang="ts">
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useApplicationRequirements } from '@/composables/institution/useApplicationRequirements';
import { useUtils } from '@/composables/core/useUtils';
import { useSubjects } from '@/composables/institution/useSubjects';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { clearFormErrors } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { DepartmentLevel, DepartmentLevelRequirement, DepartmentLevelRequirementParams } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { RadioGroupOption } from '@/types/forms';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/core/button/BaseButton.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentLevel: DepartmentLevel;
    levels: DepartmentLevel[];
    requirements?: DepartmentLevelRequirement;
}

const props = defineProps<Props>();
const { isItTrue } = useUtils();
const { requirements, departmentLevel, institutionDepartment } = props;

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { title: 'Enrolment setup', href: route('application-offerings.index') },
    {
        title: institutionDepartment.attributes.department,
        href: route('application-requirements.department', getIdParams(institutionDepartment.id?.toString() ?? '')),
    },
    { title: departmentLevel.attributes.level },
    { transKey: 'enrolment_requirements' },
];

const isOLevelRequired = ref(false);
const onlyReadWriteRequired = ref(false);
const mainSubjectsCountDisabled = ref(true);
const otherSubjectsCountDisabled = ref(true);
const mainSubjectsDisabled = ref(true);
const { listSubjects, subjects } = useSubjects();
const { storeLevelRequirements, levelRequirementsFormSchema } = useApplicationRequirements();

const form = useForm<DepartmentLevelRequirementParams>({
    is_o_level_required: isOLevelRequired.value,
    required_subjects_count: '',
    main_subjects_count: '',
    main_subject_ids: [],
    other_subjects_count: '',
    only_read_write_required: onlyReadWriteRequired.value,
    required_level_id: '',
});

onMounted(async () => {
    await listSubjects();
    form.is_o_level_required = isOLevelRequired.value = isItTrue(requirements?.attributes?.isOLevelRequired);
    form.required_subjects_count = requirements?.attributes?.requiredSubjectsCount?.toString() ?? '';
    form.main_subjects_count = requirements?.attributes?.mainSubjectsCount?.toString() ?? '';
    form.other_subjects_count = requirements?.attributes?.otherSubjectsCount?.toString() ?? '';
    form.main_subject_ids = requirements?.attributes?.mainSubjectIds ?? [];
    form.only_read_write_required = onlyReadWriteRequired.value = isItTrue(requirements?.attributes?.onlyReadWriteRequired);
    form.required_level_id = requirements?.attributes?.requiredLevelId?.toString();
    mainSubjectsCountDisabled.value = isItTrue(!requirements?.attributes?.mainSubjectsCount);
    otherSubjectsCountDisabled.value = isItTrue(!requirements?.attributes?.otherSubjectsCount);
    mainSubjectsDisabled.value = isItTrue(!requirements?.attributes?.otherSubjectsCount);
});

const departmentLevels = computed(() => props.levels.filter((item: DepartmentLevel) => item.id !== departmentLevel.id));

const onRadioChange = (value: any) => {
    form.required_level_id = value === 'none' ? null : value;
};

const options = computed(() => {
    const rows = departmentLevels.value.map(
        (item: DepartmentLevel) =>
            ({
                value: item?.attributes?.levelId?.toString(),
                label: item?.attributes?.level,
                inputId: 'radio_' + item?.attributes?.levelId?.toString(),
            }) as RadioGroupOption,
    );
    rows.push({ value: 'none', label: 'None', inputId: 'radio_none' });

    return rows;
});

const updateLevel = () => {
    form.is_o_level_required = isOLevelRequired.value;
    form.only_read_write_required = onlyReadWriteRequired.value;
    const result = levelRequirementsFormSchema(isOLevelRequired.value).safeParse(form.data());
    if (!result.success) {
        errorAlert(trans('trans.validation_failed'));
        return;
    }
    if (Number(form.main_subjects_count ?? '') > 0 && form.main_subject_ids?.length < Number(form.main_subjects_count)) {
        errorAlert(trans('trans.main_subject_not_valid', { count: form.main_subjects_count?.toString() ?? '' }));
        return;
    }
    storeLevelRequirements(
        institutionDepartment.id?.toString() ?? '',
        departmentLevel.id?.toString() ?? '',
        form,
    );
};

const onInputRequiredSubjectsCount = () => {
    clearFormErrors(form, 'required_subjects_count');
    mainSubjectsCountDisabled.value = false;
};

const onInputMainSubjectsCount = () => {
    clearFormErrors(form, 'main_subjects_count');
    otherSubjectsCountDisabled.value = false;
};

const onInputOtherSubjectsCount = () => {
    clearFormErrors(form, 'other_subjects_count');
    mainSubjectsDisabled.value = false;
};
</script>

<template>
    <Head :title="$t('trans.enrolment_requirements')" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('application-requirements.department', getIdParams(institutionDepartment.id?.toString() ?? ''))"
    >
        <form @submit.prevent="updateLevel" class="flex flex-col">
            <div class="flex flex-col space-y-3">
                <HeadingSmall :title="$t('trans.o_level_subjects')" :description="$t('trans.select_o_level_subjects')" />
                <BaseCheckbox input-id="is_o_level_required" v-model="isOLevelRequired" :label="$t('trans.is_o_level_required')" />
            </div>
            <div v-if="isOLevelRequired" class="flex flex-col">
                <div class="my-5 grid grid-cols-4 gap-3">
                    <BaseInput
                        :label="$t('trans.required_subjects_count')"
                        input-id="required_subjects_count"
                        v-model="form.required_subjects_count"
                        @input="onInputRequiredSubjectsCount"
                        :error="form.errors.required_subjects_count"
                        :is-required="true"
                    />
                    <BaseInput
                        :label="$t('trans.main_subjects_count')"
                        input-id="main_subjects_count"
                        v-model="form.main_subjects_count"
                        @input="onInputMainSubjectsCount"
                        :error="form.errors.main_subjects_count"
                        :is-required="true"
                        :disabled="mainSubjectsCountDisabled"
                    />
                    <BaseInput
                        :label="$t('trans.other_subjects_count')"
                        input-id="other_subjects_count"
                        v-model="form.other_subjects_count"
                        @input="onInputOtherSubjectsCount"
                        :error="form.errors.other_subjects_count"
                        :is-required="true"
                        :disabled="otherSubjectsCountDisabled"
                    />
                </div>
                <div v-if="subjects.length > 0" class="grid grid-cols-1 gap-x-3 md:grid-cols-3">
                    <BaseCheckbox
                        v-for="subject in subjects"
                        :key="`subject_key_${subject.id}`"
                        :input-id="`subject_id_${subject.id}`"
                        :value="subject.id"
                        v-model="form.main_subject_ids"
                        :label="subject.attributes.name"
                        :disabled="mainSubjectsDisabled"
                    />
                </div>
                <Empty v-else />
            </div>
            <div class="mt-5 flex flex-col space-y-3">
                <HeadingSmall :title="$t('trans.spd_requirements')" :description="$t('trans.spd_requirements_description')" />
                <BaseCheckbox input-id="only_read_write_required" v-model="onlyReadWriteRequired" :label="$t('trans.only_read_write_required')" />
            </div>
            <div v-if="departmentLevels.length > 0" class="mt-5 flex flex-col space-y-3">
                <HeadingSmall :title="$t('trans.requires_previous_level')" :description="$t('trans.requires_previous_level_description')" />
                <BaseRadioGroup
                    :options="options"
                    :default-value="requirements?.attributes?.requiredLevelId?.toString()"
                    :label-uppercase="true"
                    :is-required="true"
                    @update:modelValue="onRadioChange"
                />
            </div>
            <div class="flex items-center justify-center space-x-3 p-6">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    :size="ButtonSize.lg"
                    :href="route('application-requirements.department', getIdParams(institutionDepartment.id?.toString() ?? ''))"
                >
                    {{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing" :size="ButtonSize.lg">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
