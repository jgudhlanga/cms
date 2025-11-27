<script setup lang="ts">
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
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
const institutionDepartmentId = String(department?.id) ?? '';
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
            <EnrolmentFilters
                v-model:intakePeriodModel="intakePeriod"
                v-model:modeOfStudyModel="modeOfStudy"
                :intake-periods="intakePeriods?.data ?? []"
                :modes-of-study="modesOfStudy ?? []"
                :handle-filter-change="handleSelectionChange"
            />
        </div>
        <DataLoadingSpinner v-if="isLoading || intakePeriodsLoading || modesOfStudyLoading" />
        <div class="flex flex-col" v-else>
            <template v-if="enrolments && enrolments.length > 0">
                <div v-for="enrolment in enrolments" :key="enrolment.departmentCourseId" class="flex flex-col space-y-4">
                    <HeadingSmall :title="enrolment.courseName" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <template  v-for="level in enrolment.levels" >
                            <Link
                                v-if="level"
                                :key="level"
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
                        </template>
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
