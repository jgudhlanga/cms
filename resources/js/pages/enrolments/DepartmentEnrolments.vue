<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import { DepartmentEnrolmentCount } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Head, Link } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id) ?? '';
const { getData, isLoading } = useServerSide();
const { getQueryParams } = useUtils();
const classLists = ref<DepartmentEnrolmentCount[] | []>([]);
const intakePeriod = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const queryParams = getQueryParams();

onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);
    await listModesOfStudy();
    intakePeriod.value = intakePeriods.value?.data![0]?.id ?? null;
    const intakeOption = intakePeriods.value?.data![0] ?? null;
    const modeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() == 'full time')[0] ?? null;
    intakePeriod.value = intakeOption ? { value: Number(intakeOption.id), label: intakeOption.attributes.name } : null;
    modeOfStudy.value = modeOption ? { value: Number(modeOption.id), label: modeOption.attributes.name } : null;
    await loadClassLists();
});

const loadClassLists = async () => {
    const intakePeriodId = queryParams['intake_period_id'] ?? intakePeriod.value?.value.toString();
    const modeOfStudyId = queryParams['mode_of_study_id'] ?? modeOfStudy.value?.value.toString();
    classLists.value = await getData(
        route('v1.department-metadata.class-lists', {
            institution_department: institutionDepartmentId,
            intake_period_id: intakePeriodId,
            mode_of_study_id: modeOfStudyId,
            type: queryParams['type'],
        }),
        () => trans_choice('trans.enrolment', 2),
    );
};
const handleSelectionChange = async () => {
    await loadClassLists();
};

const breadcrumbs = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: department.attributes.department, href: route('enrolments.index') },
    { title: `${queryParams['type']} applications` },
] as Array<any>;
</script>

<template>
    <Head :title="$tChoice('trans.enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
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
                <template v-if="classLists && classLists.length > 0">
                    <div v-for="enrolment in classLists" :key="enrolment.departmentCourseId" class="flex flex-col space-y-4">
                        <HeadingSmall :title="enrolment.courseName" />
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                            <Link
                                v-for="level in enrolment.levels"
                                :key="level.departmentLevelId"
                                :href="
                                    route('enrolments.class-lists', {
                                        institution_department: institutionDepartmentId,
                                        department_level: level.departmentLevelId,
                                        intake_period_id: intakePeriod?.value.toString(),
                                        mode_of_study_id: modeOfStudy?.value.toString(),
                                        department_course_id: enrolment?.departmentCourseId ?? '',
                                        type: queryParams['type'],
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
                <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.ui_no_class_lists_found_for_the_selected_filters')" />
            </div>
        </div>
    </PageContainer>
</template>
