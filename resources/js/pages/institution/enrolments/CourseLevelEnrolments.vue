<script setup lang="ts">
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import ByAcademicLevelResults from '@/pages/institution/enrolments/partials/ByAcademicLevelResults.vue';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';
import DeficitInClassSize from '@/pages/institution/enrolments/partials/DeficitInClassSize.vue';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import GeneralEnrolmentClassList from '@/pages/institution/enrolments/partials/GeneralEnrolmentClassList.vue';
import GeneralEnrolments from '@/pages/institution/enrolments/partials/GeneralEnrolments.vue';
import OLevelClassList from '@/pages/institution/enrolments/partials/OLevelClassList.vue';
import ScoringFormula from '@/pages/institution/enrolments/partials/ScoringFormula.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { ClassListParams, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

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
const { allocateClassSlots, applyPolicyAlgorithmToApplications, classListIsCreated } = useEnrolments();

const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);

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

const form = useForm<ClassListParams>({
    class_list: null,
    waiting_list: null,
    type: 'provisional',
});

const otherGenderHasWaitingList = (group: EnrolmentGroup) => {
    const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };
    const { females, males } = allocateClassSlots(Number(classSize), groups.disabled.length, groups.females.length, groups.males.length);
    if (group === 'males') {
        return enrolments.groups.females.length > females;
    } else if (group === 'females') {
        return enrolments.groups.males.length > males;
    } else {
        return false;
    }
};
async function createProvisionalClass() {
    if (!hasAbility('create:class-lists')) {
        forbiddenAlert();
        return;
    }
    const confirmed = await useCustomConfirmDialog().open({
        title: 'Create Class',
        message: 'Are you sure you want to create class list and its waiting list?',
        confirmText: 'Please continue',
    });
    if (confirmed) {
        const groups = ['disabled', 'females', 'males'] as const;

        const classList: string[] = [];
        const waitingList: string[] = [];

        groups.forEach((group) => {
            const enrolmentsGroup = enrolments.groups?.[group] ?? [];
            const processedEnrolments = applyPolicyAlgorithmToApplications(enrolmentsGroup, level);
            const slot = getGroupSlot(group);

            // Add to class list
            const toClass = processedEnrolments.slice(0, slot).map((e) => String(e.applicationId));
            classList.push(...toClass);

            // Add to waiting list (next 'slot' after class list)
            const toWaiting = processedEnrolments.slice(slot, slot * 2).map((e) => String(e.applicationId));
            waitingList.push(...toWaiting);
        });

        // Assign to form
        form.class_list = classList;
        form.waiting_list = waitingList;

        // Submit form
        try {
            form.post(route('enrolments.store-class-list'), {
                onSuccess: () => {
                    successAlert('Provisional class and waiting list created successfully');
                    router.visit(window.location.href, { replace: true, preserveScroll: true });
                },
                onError: (errors: Record<string, any>) => {
                    const message = Object.keys(errors).length
                        ? Object.values(errors).join('\n')
                        : 'Provisional class and waiting list creation failed';
                    errorAlert(message);
                },
            });
        } catch {
            errorAlert('An unexpected error occurred while creating provisional class and waiting list');
        }
    }
}
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
            <!-- ============ SHOW APPLICATIONS BY GROUPS -->
            <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired) && !noData">
                <ScoringFormula :class-size="classSize" v-if="!classListIsCreated(enrolments)" />
                <div class="flex justify-between" v-else>
                    <div class="flex items-center space-x-2"></div>
                    <div class="flex items-center space-x-2">
                        <ClassSize :class-size="classSize" />
                        <DeficitInClassSize :deficit="classSize" />
                    </div>
                </div>
            </template>
            <template v-else-if="!noData && !isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                <div class="flex justify-between">
                    <div class="flex items-center space-x-2"></div>
                    <div class="flex items-center space-x-2">
                        <ClassSize :class-size="classSize" />
                        <DeficitInClassSize :deficit="classSize" />
                    </div>
                </div>
            </template>
            <div class="mt-6 flex items-center justify-end space-x-2">
                <BaseButton
                    v-if="!noData && Number(classSize) > 0 && !classListIsCreated(enrolments)"
                    type="button"
                    :variant="ColorVariant.primary"
                    :title="$t('trans.ui_create_provisional_class')"
                    classes="rounded-full normalize"
                    @click="createProvisionalClass"
                    :disabled="classListIsCreated(enrolments)"
                />
            </div>
            <div v-for="(enrolmentsInGroup, group) in enrolments.groups" :key="group" class="flex flex-col">
                <div class="flex flex-col">
                    <HeadingSmall :title="`${group} (${getGroupSlot(group.toLowerCase() as EnrolmentGroup)})`" class="mt-6" />
                    <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                        <OLevelClassList
                            v-if="classListIsCreated(enrolments)"
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="Number(classSize)"
                            :slot-size="getGroupSlot(group.toLowerCase() as EnrolmentGroup)"
                            :other-gender-has-waiting-list="otherGenderHasWaitingList(group.toLowerCase() as EnrolmentGroup)"
                        />
                        <ByAcademicLevelResults
                            v-else
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="Number(classSize)"
                            :slot-size="getGroupSlot(group.toLowerCase() as EnrolmentGroup)"
                            :other-gender-has-waiting-list="otherGenderHasWaitingList(group.toLowerCase() as EnrolmentGroup)"
                        />
                    </template>
                    <template v-else>
                        <GeneralEnrolmentClassList
                            v-if="classListIsCreated(enrolments)"
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="Number(classSize)"
                            :slot-size="getGroupSlot(group.toLowerCase() as EnrolmentGroup)"
                            :class-size-is-created="classListIsCreated(enrolments)"
                        />
                        <GeneralEnrolments
                            v-else
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="Number(classSize)"
                            :slot-size="getGroupSlot(group.toLowerCase() as EnrolmentGroup)"
                            :class-size-is-created="classListIsCreated(enrolments)"
                        />
                    </template>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
