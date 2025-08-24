<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import EclipseButton from '@/components/core/button/EclipseButton.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import PaymentProofPreviewModal from '@/pages/institution/enrolments/partials/PaymentProofPreviewModal.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { Enrolment } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import BaseButton from '../../../components/core/button/BaseButton.vue';
import OLevelBased from './OLevelBased.vue';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    enrolments: Record<string, Enrolment[]>;
    workflowSteps: DepartmentApplicationStep[];
    intakePeriod: IntakePeriod;
    modeOfStudy: ModeOfStudy;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { department, level, enrolments, intakePeriod, modeOfStudy } = props;
const { isItTrue } = useUtils();

const { bulkApproveApplication, canApproveWorkflowStepApplications } = useStudentApplications();
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const intakePeriodModel = ref<SelectOption | null>(null);
const modeOfStudyModel = ref<SelectOption | null>(null);

onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);
    await listModesOfStudy();
    intakePeriodModel.value = intakePeriod ? { value: Number(intakePeriod.id), label: intakePeriod.attributes.name } : null;
    modeOfStudyModel.value = modeOfStudy ? { value: Number(modeOfStudy.id), label: modeOfStudy.attributes.name } : null;
});
const firstStepKey = Object.keys(enrolments)[0] ?? '';
const firstEnrolment = firstStepKey ? enrolments[firstStepKey]?.[0] : null;
const firstCourseName = firstEnrolment?.attributes?.course ?? '';

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { title: firstCourseName },
    { transChoiceKey: 'enrolment' },
];

const sortedEnrolmentsByStep = computed(() => {
    const sorted: Record<string, Enrolment[]> = {};

    // Loop over each workflow step
    for (const [step, enrolmentArray] of Object.entries(enrolments)) {
        sorted[step] = [...enrolmentArray].sort((a, b) => {
            const totalA = a.relationships?.oLevelResults?.reduce((sum, result) => sum + Number(result.attributes.gradePosition || 0), 0) ?? 0;

            const totalB = b.relationships?.oLevelResults?.reduce((sum, result) => sum + Number(result.attributes.gradePosition || 0), 0) ?? 0;

            return totalA - totalB; // lowest total first
        });
    }

    return sorted;
});

const getNextSteps = (currentStepName: string) => {
    const currentStepObj = getCurrentStep(currentStepName);

    if (!currentStepObj) {
        return []; // No matching step found
    }

    return props.workflowSteps.filter((step: DepartmentApplicationStep) => step.attributes.position > currentStepObj.attributes.position);
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
            institution_department: department.id?.toString(),
            department_level: level.id?.toString(),
            intake_period_id: intakePeriodId,
            mode_of_study_id: modeOfStudyId,
        }),
        {
            preserveScroll: true,
            preserveState: false, // full reload
            replace: true, // don’t pollute browser history
        },
    );
};
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full items-center justify-between space-x-4">
            <IntakePeriodComboSelect
                :loading="intakePeriodsLoading"
                :data="intakePeriods?.data ?? []"
                :label-uppercase="true"
                v-model="intakePeriodModel"
                :vertical-layout="false"
                :is-required="true"
                @update:modelValue="handleFilterChange"
                class="w-full"
            />
            <ModeOfStudyComboSelect
                :loading="modesOfStudyLoading"
                :data="modesOfStudy ?? []"
                v-model="modeOfStudyModel!"
                @update:modelValue="handleFilterChange"
                :vertical-layout="false"
                :label-uppercase="true"
                :is-required="true"
                class="w-full"
            />
        </div>
        <CustomSeparator classes="h-[1px] my-3" />
        <template v-if="!(Object.entries(sortedEnrolmentsByStep).length === 0)">
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
                                <BaseButton :variant="ColorVariant.success" :size="ButtonSize.xs" classes="rounded-full">
                                    {{ $t('trans.generate_application_fee_verification_report') }}
                                </BaseButton>
                            </template>
                            <template v-if="action.action.toLowerCase() == 'verify-tuition-fee-payment-with-accounts'">
                                <BaseButton :variant="ColorVariant.success" :size="ButtonSize.xs" classes="rounded-full">
                                    {{ $t('trans.generate_tuition_fee_verification_report') }}
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
                        <OLevelBased :enrolments="enrolmentsInStep" :level="level" :steps="getNextSteps(step)" :step="getCurrentStep(step)!" />
                    </template>
                </div>
            </div>
        </template>
        <BaseAlert
            v-else
            :title="$t('trans.no_data')"
            :description="
                $t('trans.no_data_found_description', {
                    data: `${$tChoice('trans.enrolment', 2)} for ${intakePeriodModel?.label} - ${modeOfStudyModel?.label}`,
                })
            "
        />
        <PaymentProofPreviewModal />
    </PageContainer>
</template>
