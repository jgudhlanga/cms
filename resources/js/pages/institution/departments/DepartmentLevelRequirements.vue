<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import SharedNumberField from '@/components/core/form/number/SharedNumberField.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useSubjects } from '@/composables/institution/useSubjects';
import { ColorVariant } from '@/enums/colors';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { DepartmentLevelRequirementParams, InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import BaseButton from '../../../components/core/button/BaseButton.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { Title } from '@/types/settings';
import { SelectOption } from '@/types/utils';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentLevel: DepartmentLevel;
    levels: DepartmentLevel[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { departmentLevel, institutionDepartment } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    {
        title: institutionDepartment.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment.id?.toString() ?? '')),
    },
    { title: departmentLevel.attributes.level },
    { transKey: 'level_requirements' },
];

const isOLevelRequired = ref(false);
const onlyReadWriteRequired = ref(false);
const isPreviousLevelRequired = ref(false);
const { listSubjects, subjects } = useSubjects();

onMounted(async () => {
    await listSubjects();
});

const { navigateTo } = useUtils();
const form = useForm<DepartmentLevelRequirementParams>({
    is_o_level_required: isOLevelRequired.value,
    required_subjects_count: null,
    main_subjects_count: null,
    main_subject_ids: [],
    other_subjects_count: null,
    only_read_write_required: false,
    is_previous_level_required: isPreviousLevelRequired.value,
    previous_level_id: null,
});

const selectOLevelRequired = (value: any) => {
    isOLevelRequired.value = value;
    if (value) {
        onlyReadWriteRequired.value = false;
        isPreviousLevelRequired.value = false;
    }
};

const SelectOnlyReadWriteRequired = (value: any) => {
    onlyReadWriteRequired.value = value;
    isPreviousLevelRequired.value = !value;
};

const selectPreviousLevelRequired = (value: any) => {
    isPreviousLevelRequired.value = value;
    onlyReadWriteRequired.value = !value;
};

const departmentLevels = computed(() =>
    props.levels.filter(
        (item: DepartmentLevel) => item.id !== departmentLevel.id && item.attributes.levelPosition < departmentLevel.attributes.levelPosition,
    ),
);
const options = computed(() => {
    return departmentLevels.value.map((item: DepartmentLevel) => <SelectOption>{
        value: Number(item?.attributes?.levelId),
        label: item?.attributes?.level
    });
});

const updateLevel = () => {};
</script>

<template>
    <Head :title="$t('trans.level_requirements')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => updateLevel()" class="flex flex-col">
            <div class="flex flex-col">
                <div class="flex flex-col space-y-3">
                    <HeadingSmall :title="$t('trans.o_level_subjects')" :description="$t('trans.select_o_level_subjects')" />
                    <BaseCheckbox
                        input-id="is_o_level_required"
                        @click="selectOLevelRequired($event.target.checked)"
                        v-model="isOLevelRequired"
                        :label="`${$t('trans.is_o_level_required')}`"
                    />
                </div>
                <div v-if="isOLevelRequired" class="flex flex-col">
                    <div class="my-5 grid grid-cols-4 gap-3">
                        <SharedNumberField
                            label="Required subjects count"
                            input-id="required_subjects_count"
                            v-model="form.required_subjects_count"
                        />
                        <SharedNumberField label="Main subjects count" input-id="main_subjects_count" v-model="form.main_subjects_count" />
                        <SharedNumberField label="Other subjects count" input-id="other_subjects_count" v-model="form.other_subjects_count" />
                    </div>
                    <template v-if="subjects && subjects.length > 0">
                        <div class="flex flex-col">
                            <HeadingSmall :title="$t('trans.main_subjects')" :description="$t('trans.select_main_required_subjects')" />
                            <div class="grid grid-cols-1 gap-x-3 md:grid-cols-3">
                                <template v-for="subject in subjects" :key="`subject_key_${subject['id']}`">
                                    <BaseCheckbox
                                        :input-id="`subject_id_${subject['id']}`"
                                        :value="subject['id']"
                                        v-model="form.main_subject_ids"
                                        :label="subject['attributes']['name']"
                                    />
                                </template>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Empty />
                    </template>
                </div>
                <template v-else>
                    <div class="flex flex-col">
                        <HeadingSmall class="mt-5" :title="$t('trans.spd_requirements')" :description="$t('trans.spd_requirements_description')" />
                        <BaseCheckbox
                            input-id="only_read_write_required"
                            @click="SelectOnlyReadWriteRequired($event.target.checked)"
                            :label="`${$t('trans.only_read_write_required')}`"
                            v-model="onlyReadWriteRequired"
                        />
                    </div>
                    <div class="mt-5 flex flex-col space-y-3">
                        <HeadingSmall :title="$t('trans.requires_previous_level')" :description="$t('trans.requires_previous_level_description')" />
                        <BaseCheckbox
                            input-id="is_previous_level_required"
                            v-model="isPreviousLevelRequired"
                            :label="`${$t('trans.requires_previous_level')}`"
                            @click="selectPreviousLevelRequired($event.target.checked)"
                        />
                        <div v-if="isPreviousLevelRequired">
                            <template v-if="departmentLevels && departmentLevels.length > 0">
                                <div class="flex flex-col">
                                    <BaseCombobox
                                        class="w-1/4"
                                        :label="$tChoice('trans.level', 1)"
                                        :options="options"
                                        :label-uppercase="false"
                                        :vertical-layout="true"
                                        :is-required="true"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                <Empty />
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex items-center justify-center space-x-3 p-6">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    @click="
                        () =>
                            navigateTo(
                                route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? '')),
                            )
                    "
                    >{{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing"> {{ $t('trans.save') }} </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
