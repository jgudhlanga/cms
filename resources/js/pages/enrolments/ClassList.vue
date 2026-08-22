<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import ClassListTable from '@/pages/enrolments/partials/ClassListTable.vue';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { ClassListType, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link as BreadcrumbLink } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { trans_choice } from 'laravel-vue-i18n';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: { name?: string; department_course_id?: number | string } | null;
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
const { allocateClassSlots, applyPolicyAlgorithmToApplications } = useEnrolments();
const { getQueryParams, isItTrue } = useUtils();

const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);
const queryParams = getQueryParams();

onMounted(async () => {
    intakePeriodModel.value = intakePeriod ? { value: Number(intakePeriod.id), label: intakePeriod.attributes.name } : null;
    modeOfStudyModel.value = modeOfStudy ? { value: Number(modeOfStudy.id), label: modeOfStudy.attributes.name } : null;
});

const listTypeLabel = computed(() => {
    const type = String(queryParams['type'] ?? '').trim();

    if (type) {
        return `${type} ${trans_choice('trans.application', 2)}`;
    }

    return trans_choice('trans.class_list', 1);
});

const enrolmentsBackUrl = computed(() =>
    route('department-levels.enrolments', {
        institution_department: String(department.id),
        department_level: String(level.id),
        intake_period_id: String(intakePeriod.id),
        mode_of_study_id: String(modeOfStudy.id),
        department_course_id: String(course?.department_course_id ?? ''),
    }),
);

const breadcrumbs: Array<BreadcrumbLink> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    {
        transChoiceKey: 'department',
        href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }),
    },
    {
        title: department.attributes.department,
        href: route('institution-departments.show', {
            department: String(department.id),
            intake_period_id: String(intakePeriod.id),
            mode_of_study_id: String(modeOfStudy.id),
        }),
    },
    { title: level.attributes.level, href: enrolmentsBackUrl.value },
    { title: course?.name ?? '', href: enrolmentsBackUrl.value },
    { title: listTypeLabel.value },
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
            department_course_id: String(course?.department_course_id ?? ''),
            type: queryParams['type'] || undefined,
        }),
    );
};

const noData = computed(
    () =>
        (enrolments.groups?.disabled?.length ?? 0) === 0 &&
        (enrolments.groups?.females?.length ?? 0) === 0 &&
        (enrolments.groups?.males?.length ?? 0) === 0,
);

const totalApplications = computed(() => {
    return (enrolments.groups?.disabled?.length ?? 0) + (enrolments.groups?.females?.length ?? 0) + (enrolments.groups?.males?.length ?? 0);
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
    }

    return groups[group]?.length ?? 0;
};

const genderGroups = ['disabled', 'females', 'males'] as const;
</script>

<template>
    <Head :title="listTypeLabel" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="enrolmentsBackUrl">
        <template #backNavigationLeading>
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold uppercase">{{ course?.name }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ level.attributes.level }} · {{ modeOfStudy.attributes.name }} · {{ listTypeLabel }}
                </p>
            </div>
        </template>

        <EnrolmentFilters
            v-model:intakePeriodModel="intakePeriodModel"
            v-model:modeOfStudyModel="modeOfStudyModel"
            :intake-periods="intakePeriods"
            :modes-of-study="modesOfStudy"
            :handle-filter-change="handleFilterChange"
        />
        <div class="my-6 flex flex-col">
            <BaseAlert
                v-if="noData"
                :title="$t('trans.no_data')"
                :description="
                    $t('trans.no_data_found_description', {
                        data: `${$tChoice('trans.class_list', 2)} for ${intakePeriodModel?.label} - ${modeOfStudyModel?.label}`,
                    })
                "
            />
            <div class="flex justify-end space-x-2" v-if="!noData">
                <ClassSize :class-size="classSize" />
            </div>
            <div v-for="group in genderGroups" :key="group" class="flex flex-col">
                <div class="flex flex-col" v-if="Number(getGroupSlot(group)) > 0">
                    <HeadingSmall :title="`${group} (${getGroupSlot(group)})`" class="mt-6" />
                    <ClassListTable
                        v-if="isItTrue(level?.relationships?.requirement?.attributes?.isOLevelRequired)"
                        :class-list-type="(queryParams['type'] as ClassListType) || undefined"
                        :department-id="String(department?.id)"
                        :applications="applyPolicyAlgorithmToApplications(enrolments.groups[group], level)"
                    />
                    <ClassListTable
                        v-else
                        :class-list-type="(queryParams['type'] as ClassListType) || undefined"
                        :department-id="String(department?.id)"
                        :applications="enrolments.groups[group]"
                    />
                </div>
            </div>
        </div>
    </PageContainer>
</template>
