<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Avatar from '@/components/core/util/Avatar.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import { DepartmentEnrolmentCount } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Link } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = department?.id?.toString() ?? '';
const { getData, isLoading } = useServerSide();
const enrolments = ref<DepartmentEnrolmentCount[] | []>([]);
const intakePeriod = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();

onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);
    await listModesOfStudy();
    intakePeriod.value = intakePeriods.value?.data![0]?.id ?? null;
    const intakeOption = intakePeriods.value?.data![0] ?? null;
    const modeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() == 'full time')[0] ?? null;
    intakePeriod.value = intakeOption ? { value: Number(intakeOption.id), label: intakeOption.attributes.name } : null;
    modeOfStudy.value = modeOption ? { value: Number(modeOption.id), label: modeOption.attributes.name } : null;
    await loadEnrolments();
});

const loadEnrolments = async () => {
    enrolments.value = await getData(
        `api/v1/departments/${institutionDepartmentId}/enrolments?intake_period_id=${intakePeriod.value?.value.toString()}&mode_of_study_id=${modeOfStudy.value?.value.toString()}`,
        () => trans_choice('trans.enrolment', 2),
    );
};
const handleSelectionChange = async () => {
    await loadEnrolments();
};
</script>

<template>
    <div class="my-8 flex flex-col space-y-4">
        <div class="mb-8 flex w-full justify-between space-x-4">
            <div class="flex w-1/2">
                <IntakePeriodComboSelect
                    :loading="intakePeriodsLoading"
                    :data="intakePeriods?.data ?? []"
                    :label-uppercase="true"
                    v-model="intakePeriod"
                    :vertical-layout="false"
                    :is-required="true"
                    @update:modelValue="handleSelectionChange"
                    class="w-full"
                />
            </div>
            <div class="flex w-1/2">
                <ModeOfStudyComboSelect
                    :loading="modesOfStudyLoading"
                    :data="modesOfStudy ?? []"
                    v-model="modeOfStudy!"
                    @update:modelValue="handleSelectionChange"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    :is-required="true"
                    class="w-full"
                />
            </div>
        </div>
        <DataLoadingSpinner v-if="isLoading || intakePeriodsLoading" />
        <div class="flex flex-col" v-else>
            <template v-if="enrolments && enrolments.length > 0">
                <div v-for="enrolment in enrolments" :key="enrolment.departmentCourseId" class="flex flex-col space-y-4">
                    <HeadingSmall :title="enrolment.courseName" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <Link
                            v-for="level in enrolment.levels"
                            :key="level.departmentLevelId"
                            :href="
                                route('department-levels.enrolments', {
                                    institution_department: institutionDepartmentId,
                                    department_level: level.departmentLevelId,
                                    intake_period_id: intakePeriod?.value.toString(),
                                    mode_of_study_id: modeOfStudy?.value.toString(),
                                    department_course_id: enrolment?.departmentCourseId ?? '',
                                })
                            "
                        >
                            <div class="flex items-center space-x-2">
                                <ItemTitle :title="level.levelName" class="text-primary font-bold" />
                                <Avatar src="" :name="level.enrolmentsCount" :is-number="true" class="bg-primary text-white" />
                            </div>
                        </Link>
                    </div>
                    <CustomSeparator classes="h-[1px] my-3" />
                </div>
            </template>
            <BaseAlert
                v-else
                :title="$t('trans.no_data')"
                :description="$t('trans.no_data_found_description', { data: $tChoice('trans.enrolment', 2) })"
            />
        </div>
    </div>
</template>
