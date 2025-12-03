<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { IconName } from '@/lib/icons';
import ClassListTable from '@/pages/enrolments/partials/ClassListTable.vue';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { ClassListType, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, PropType, ref } from 'vue';
import { hasAbility } from '@/lib/permissions';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: object;
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
const { allocateClassSlots } = useEnrolments();
const { getQueryParams } = useUtils();

const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);
const queryParams = getQueryParams();
onMounted(async () => {
    intakePeriodModel.value = intakePeriod ? { value: Number(intakePeriod.id), label: intakePeriod.attributes.name } : null;
    modeOfStudyModel.value = modeOfStudy ? { value: Number(modeOfStudy.id), label: modeOfStudy.attributes.name } : null;
});

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    {
        title: department.attributes.department,
        href: route('enrolments.department-applications', { institution_department: String(department?.id) }),
    },
    {
        title: level.attributes.level,
        href: route('enrolments.department-applications', { institution_department: String(department?.id), type: queryParams['type'] }),
    },
    {
        title: course?.name,
        href: route('enrolments.department-applications', { institution_department: String(department?.id), type: queryParams['type'] }),
    },
    { title: `${queryParams['type']} class list` },
];

const handleFilterChange = () => {
    const intakePeriodId = intakePeriodModel.value?.value ?? null;
    const modeOfStudyId = modeOfStudyModel.value?.value ?? null;
    router.get(
        route('enrolments.class-lists', {
            institution_department: String(department.id),
            department_level: String(level.id),
            intake_period_id: intakePeriodId,
            mode_of_study_id: modeOfStudyId,
            department_course_id: String(course?.department_course_id),
            type: queryParams['type'],
        }),
    );
};
const noData = computed(
    () => enrolments.groups.disabled.length === 0 && enrolments.groups.females.length === 0 && enrolments.groups.males.length === 0,
);

const totalApplications = computed(() => {
    return enrolments.groups.disabled.length + enrolments.groups.females.length + enrolments.groups.males.length;
});
const getGroupSlot = (group: EnrolmentGroup): number => {
    const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };
    if (totalApplications.value > Number(classSize)) {
        const { disabled, females, males } = allocateClassSlots(
            Number(classSize),
            groups.disabled.length,
            groups.females.length,
            groups.males.length,
        );
        const slots = { disabled, females, males };
        return slots[group] ?? 0;
    } else {
        // If total applications are less than or equal to class size, allocate all to class list
        return groups[group]?.length ?? 0;
    }
};
</script>

<template>
    <Head :title="$tChoice('trans.enrolment', 2)" />
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
                v-if="noData"
                :title="$t('trans.no_data')"
                :description="
                    $t('trans.no_data_found_description', {
                        data: `${$tChoice('trans.enrolment', 2)} for ${intakePeriodModel?.label} - ${modeOfStudyModel?.label}`,
                    })
                "
            />
            <div class="flex justify-end space-x-2" v-if="!noData">
                <ClassSize :class-size="classSize" />
                <BaseButton title="Configure Classes" classes="rounded-full" v-if="hasAbility('manage-final:class-lists')">
                    <BaseIcon :name="IconName.cogs" />
                </BaseButton>
            </div>
            <div v-for="(enrolmentsInGroup, group) in enrolments.groups" :key="group" class="flex flex-col">
                <div class="flex flex-col" v-if="Number(getGroupSlot(group.toLowerCase() as EnrolmentGroup)) > 0">
                    <HeadingSmall :title="`${group} (${getGroupSlot(group.toLowerCase() as EnrolmentGroup)})`" class="mt-6" />
                    <ClassListTable
                        :class-list-type="queryParams['type'] as PropType<ClassListType>"
                        :department-id="String(department?.id)"
                        :applications="enrolmentsInGroup"
                        :class-size="Number(classSize)"
                    />
                </div>
            </div>
        </div>
    </PageContainer>
</template>
