<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import ByAcademicLevelResults from '@/pages/institution/enrolments/partials/ByAcademicLevelResults.vue';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import PaymentProofPreviewModal from '@/pages/institution/enrolments/partials/PaymentProofPreviewModal.vue';
import ScoringFormula from '@/pages/institution/enrolments/partials/ScoringFormula.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep, DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { Enrolment, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: DepartmentCourse;
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

const { department, level, enrolments, intakePeriod, modeOfStudy, course } = props;
const { isItTrue } = useUtils();

const { bulkApproveApplication, canApproveWorkflowStepApplications } = useStudentApplications();
const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);

onMounted(async () => {
    intakePeriodModel.value = intakePeriod ? { value: Number(intakePeriod.id), label: intakePeriod.attributes.name } : null;
    modeOfStudyModel.value = modeOfStudy ? { value: Number(modeOfStudy.id), label: modeOfStudy.attributes.name } : null;
});

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { title: course?.attributes?.course },
    { transChoiceKey: 'enrolment' },
];


const getNextSteps = (currentStepName: string) => {
    const currentStepObj = getCurrentStep(currentStepName);

    if (!currentStepObj) {
        return []; // No matching step found
    }

    return props.workflowSteps.filter((step: DepartmentApplicationStep) => {
        return step;
        // const roles = step.relationships?.metadata?.roles ?? [];
        // const hasStudent = roles.some((role) => role?.toLowerCase() === 'student');
        //return step.attributes.position > currentStepObj.attributes.position && !hasStudent;
    });
};

const getCurrentStep = (currentStepName: string) => {
    return props.workflowSteps.find((step: DepartmentApplicationStep) => step.attributes.workflowStep === currentStepName);
};

const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});

const buttonOptions = (currentStepName: string, enrolments: Enrolment[]) => {
    const options = getNextSteps(currentStepName);
    const choices = [];
    for (const option of options) {
        choices.push({
            key: option.id,
            id: option.id,
            title: option?.attributes?.workflowStep,
            action: () =>
                bulkApproveApplication(
                    department.id?.toString() ?? '',
                    {
                        intake_period_id: intakePeriod?.id?.toString() ?? '',
                        department_level_id: level.id?.toString() ?? '',
                        mode_of_study_id: modeOfStudy?.id?.toString() ?? '',
                        current_step_id: getCurrentStep(currentStepName)?.id?.toString() ?? '',
                        new_step_id: option.id?.toString() ?? '',
                    },
                    enrolments,
                    getCurrentStep(currentStepName)!,
                ),
        });
    }
    return choices;
};

const handleFilterChange = () => {
    const intakePeriodId = intakePeriodModel.value?.value ?? null;
    const modeOfStudyId = modeOfStudyModel.value?.value ?? null;
    router.get(
        route('department-levels.enrolments', {
            institution_department: String(department.id),
            department_level: String(level.id),
            intake_period_id: intakePeriodId,
            mode_of_study_id: modeOfStudyId,
            department_course_id: String(course?.id),
        }),
    );
};
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
            <div v-for="(enrolmentsInGroup, group) in enrolments.groups" :key="group" class="my-5 flex flex-col">
                <div class="flex flex-col space-y-3">
                    <HeadingSmall :title="group" />
                    <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                        <ByAcademicLevelResults
                            :level="level"
                            :department-id="String(department?.id)"
                            :applications="enrolmentsInGroup"
                            :class-size="classSize"
                        />
                    </template>
                </div>
            </div>
            <!--        <template v-if="!(Object.entries(sortedEnrolmentsByStep).length === 0)">
            <div v-for="(enrolmentsInStep, step) in sortedEnrolmentsByStep" :key="step" class="flex flex-col space-y-3">
                <div class="mt-7 flex items-center justify-between">
                    <div class="text-accent-foreground mb-0.5 flex text-sm font-bold uppercase">{{ step }}</div>
                    <div class="flex space-x-2">
                        <div
                            class="flex flex-col space-y-3"
                            v-for="action in getCurrentStep(step)?.relationships?.metadata?.actions"
                            :key="action.action"
                        >
                            <template v-if="action.action.toLowerCase() == 'verify-application-fee-payment-with-accounts'">
                                <BaseButton
                                    :variant="ColorVariant.success"
                                    :size="ButtonSize.xs"
                                    :disabled="!canApproveWorkflowStepApplications(getCurrentStep(step)!)"
                                >
                                    {{ $t('trans.verification_report') }}
                                </BaseButton>
                            </template>
                            <template v-if="action.action.toLowerCase() == 'verify-tuition-fee-payment-with-accounts'">
                                <BaseButton
                                    :variant="ColorVariant.success"
                                    :size="ButtonSize.xs"
                                    :disabled="!canApproveWorkflowStepApplications(getCurrentStep(step)!)"
                                >
                                    {{ $t('trans.verification_report') }}
                                </BaseButton>
                            </template>
                        </div>
                        <EclipseButton
                            :disabled="!canApproveWorkflowStepApplications(getCurrentStep(step)!)"
                            :options="buttonOptions(step, enrolmentsInStep)"
                            :group-title="$t('trans.send_all_applications_to')"
                            :show-group-icon="true"
                        />
                    </div>
                </div>
                <div class="inline-block min-w-full overflow-auto align-middle">
                    <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                        <OLevelBased
                            :enrolments="enrolmentsInStep"
                            :level="level"
                            :steps="getNextSteps(step)"
                            :step="getCurrentStep(step)!"
                            :departmentId="department?.id?.toString() ?? ''"
                            :update-payment-status-params="{
                                intake_period_id: intakePeriod?.id?.toString() ?? '',
                                mode_of_study_id: modeOfStudy?.id?.toString() ?? '',
                                department_level_id: level?.id?.toString() ?? '',
                                step: getCurrentStep(step) ?? null,
                            }"
                        />
                    </template>
                    <template v-else>
                        <GeneralEnrolments
                            :enrolments="enrolmentsInStep"
                            :level="level"
                            :steps="getNextSteps(step)"
                            :step="getCurrentStep(step)!"
                            :departmentId="department?.id?.toString() ?? ''"
                            :update-payment-status-params="{
                                intake_period_id: intakePeriod?.id?.toString() ?? '',
                                mode_of_study_id: modeOfStudy?.id?.toString() ?? '',
                                department_level_id: level?.id?.toString() ?? '',
                                step: getCurrentStep(step) ?? null,
                            }"
                        />
                    </template>
                </div>
            </div>
        </template>-->
        </div>
        <PaymentProofPreviewModal />
    </PageContainer>
</template>
