<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ColorVariant } from '@/enums/colors';
import { getIdParams } from '@/lib/utils';
import ByAcademicLevelResults from '@/pages/institution/enrolments/partials/ByAcademicLevelResults.vue';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import ScoringFormula from '@/pages/institution/enrolments/partials/ScoringFormula.vue';
import { useClassListStore } from '@/store/enrolments/useClassListStore';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeMount, onMounted, ref } from 'vue';

type GroupType = 'disabled' | 'females' | 'males';
interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: object;
    workflowSteps: DepartmentApplicationStep[];
    intakePeriod: IntakePeriod;
    modeOfStudy: ModeOfStudy;
    auth: AuthObject;
    errors: object;
    intakePeriods: IntakePeriod[];
    modesOfStudy: ModeOfStudy[];
    enrolments: EnrolmentGroupResponse;
    classSize: string | number;
}

const props = defineProps<Props>();

const { department, level, enrolments, intakePeriod, modeOfStudy, course, classSize } = props;
const { isItTrue } = useUtils();
const { allocateClassSlots } = useEnrolments();

const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);
const classListStore = useClassListStore();

onMounted(async () => {
    intakePeriodModel.value = intakePeriod ? { value: Number(intakePeriod.id), label: intakePeriod.attributes.name } : null;
    modeOfStudyModel.value = modeOfStudy ? { value: Number(modeOfStudy.id), label: modeOfStudy.attributes.name } : null;
});

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', getIdParams(String(department?.id))) },
    { title: level.attributes.level, href: route('institution-departments.show', getIdParams(String(department?.id))) },
    { title: course?.name, href: route('institution-departments.show', getIdParams(String(department?.id))) },
    { transChoiceKey: 'enrolment' },
];

const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});

const handleFilterChange = () => {
    const intakePeriodId = intakePeriodModel.value?.value ?? null;
    const modeOfStudyId = modeOfStudyModel.value?.value ?? null;
    router.get(
        route('department-levels.enrolments', {
            institution_department: String(department.id),
            department_level: String(level.id),
            intake_period_id: intakePeriodId,
            mode_of_study_id: modeOfStudyId,
            department_course_id: String(course?.department_course_id),
        }),
    );
};

const getGroupSlot = (group: GroupType): number => {
    const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };

    const { disabled, females, males } = allocateClassSlots(Number(classSize), groups.disabled.length, groups.females.length, groups.males.length);

    const slots = { disabled, females, males };
    return slots[group] ?? 0;
};

function createProvisionalClass() {
    console.log(classListStore.classList);
}

onBeforeMount(() => {
    classListStore.$reset();
    classListStore.$dispose();
});
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <EnrolmentFilters
            v-model:intakePeriodModel="intakePeriodModel"
            v-model:modeOfStudyModel="modeOfStudyModel"
            :intake-periods="intakePeriods"
            :modes-of-study="modesOfStudy"
            :handle-filter-change="handleFilterChange"
        />
        <div class="my-6 flex flex-col">
            <!-- ============ SHOW ALERT IF NO DATA FOUND -->
            <BaseAlert
                v-if="enrolments.groups.disabled.length === 0 && enrolments.groups.females.length === 0 && enrolments.groups.males.length === 0"
                :title="$t('trans.no_data')"
                :description="
                    $t('trans.no_data_found_description', {
                        data: `${$tChoice('trans.enrolment', 2)} for ${intakePeriodModel?.label} - ${modeOfStudyModel?.label}`,
                    })
                "
            />
            <!-- ============ SHOW APPLICATIONS BY GROUPS -->
            <ScoringFormula :class-size="classSize" v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)" />
            <div class="mt-6 flex items-center justify-end">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.danger"
                    title="Create Provisional Class"
                    classes="rounded-full normalize"
                    @click="createProvisionalClass"
                />
            </div>
            <div v-for="(enrolmentsInGroup, group) in enrolments.groups" :key="group" class="flex flex-col">
                <div class="flex flex-col">
                    <HeadingSmall :title="`${group} (${getGroupSlot(group.toLowerCase() as GroupType)})`" class="mt-6" />
                    <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                        <ByAcademicLevelResults
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="Number(classSize)"
                            :slot-size="getGroupSlot(group.toLowerCase() as GroupType)"
                        />
                    </template>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
