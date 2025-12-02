<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import ClassFilters from '@/pages/institution/departments/partials/classes/ClassFilters.vue';
import { DepartmentEnrolmentCount } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id) ?? '';
const { isLoading } = useServerSide();
const enrolments = ref<DepartmentEnrolmentCount[] | []>([]);
const modeOfStudy = ref<SelectOption | null>(null);
const academicYear = ref<SelectOption | null>(null);

const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const { academicYears } = useUtils();
onMounted(async () => {
    await listModesOfStudy();
    const modeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() == 'full time')[0] ?? null;
    modeOfStudy.value = modeOption ? { value: Number(modeOption.id), label: modeOption.attributes.name } : null;
    academicYear.value = { value: new Date().getFullYear(), label: new Date().getFullYear().toString() };
});

const handleSelectionChange = async () => {};
</script>

<template>
    <div class="my-8 flex flex-col space-y-4">
        <div class="mb-8 flex w-full">
            <ClassFilters
                v-model:modeOfStudyModel="modeOfStudy"
                v-model:academicYearModel="academicYear"
                :modes-of-study="modesOfStudy ?? []"
                :academic-years="academicYears ?? []"
                :handle-filter-change="handleSelectionChange"
            />
        </div>
        <DataLoadingSpinner v-if="isLoading || modesOfStudyLoading" />
        <div class="flex flex-col" v-else>
            <template v-if="enrolments && enrolments.length > 0">
                <div v-for="enrolment in enrolments" :key="enrolment.departmentCourseId" class="flex flex-col space-y-4">
                    <HeadingSmall :title="enrolment.courseName" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <template v-for="level in enrolment.levels">
                            <Link
                                v-if="level"
                                :key="level"
                                :href="
                                    route('department-levels.enrolments', {
                                        institution_department: institutionDepartmentId,
                                        department_level: level.departmentLevelId,
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
                :description="$t('trans.no_data_found_description', { data: $tChoice('trans.class', 2) })"
            />
        </div>
    </div>
</template>
