<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Student, StudentProgram } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';
import TimelineTwo from '@/components/core/timelines/TimelineTwo.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import StepAction from '@/components/core/timelines/StepAction.vue';
import UploadPop from '@/components/shared/workflows/UploadPop.vue';
import { Audit } from '@/types/audit';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    application: StudentProgram;
    audit: Audit[];
}

const props = defineProps<Props>();
const { user } = props.auth;

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: user.attributes?.name },
    { transKey: 'track_application' },
    { title: props.application?.attributes?.applicationTrackingNumber },
];

const workflowSteps = ref<DepartmentApplicationStep[]>([]);
const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();

onMounted(async () => {
    const data = await loadDepartmentMetadata(
        route('v1.department-metadata.workflow-steps', props.application?.attributes?.institutionDepartmentId?.toString()),
    );
    workflowSteps.value = data?.steps;
});

const auditSteps = computed(() => {
    return props.audit?.map((entry) => entry.attributes.properties.department_application_step_id)
        .filter((id) => id !== undefined && id !== null);
});

const steps = computed(() => {
    if (!workflowSteps.value || currentStep.value == null) return [];
    return workflowSteps.value?.map(
        (step: DepartmentApplicationStep, index: number) =>
            <TimelineStep>{
                title: step.attributes?.workflowStep,
                description: step.attributes?.workflowStepDescription,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${index + 1}`,
                status: getStepStatus(step),
                props: {
                    step
                },
            },
    );
});
const completedActiveSteps = computed(() => {
    if (!workflowSteps.value || currentStep.value == null) return [];

    return workflowSteps.value
        .filter((step) => {
            const position = Number(step.attributes?.position);
            const inAudit = auditSteps.value.includes(step.id); // check audit
            return position <= Number(currentStep.value?.attributes.position) && inAudit;
        })
        .map((step, index) => {
            return <TimelineStep>{
                title: step.attributes?.workflowStep,
                description: step.attributes?.workflowStepDescription,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${index + 1}`,
                status: getStepStatus(step),
                component: StepAction,
                props: {
                    step,
                    status: getStepStatus(step),
                },
            };
        });
});
// Current step index based on status
const currentStepIndex = computed(() => {
    const index = workflowSteps.value.findIndex((step: DepartmentApplicationStep) => step.id == currentStep.value?.id);
    return index >= 0 ? index : 0;
});

// Progress % for progress bar
/*const progressPercent = computed(() => {
    const total = workflowSteps.value.length;
    const index = currentStepIndex.value;
    return Math.round(((index + 1) / total) * 100);
});*/

const currentStep = computed(() => {
    return props.application?.relationships?.departmentWorkflowStep;
});

const getStepStatus = (step: DepartmentApplicationStep): string => {
    if (step.id === currentStep.value?.id) {
        return 'active';
    } else if (workflowSteps.value.indexOf(step) < currentStepIndex.value) {
        return 'completed';
    }
    return 'pending';
};
</script>
<template>
    <Head :title="$tChoice('trans.application', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="isLoading">
            <DataLoadingSpinner />
        </template>
        <template v-else>
            <template v-if="steps?.length > 0">
<!--                <div class="my-5">
                    <div class="h-6 w-full overflow-hidden rounded-full bg-gray-200">
                        <div class="bg-primary h-full transition-all duration-300" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <p class="text-muted-foreground my-3 text-sm font-bold">
                        {{ $t('trans.progress') }}: {{ progressPercent }}% ({{ currentStep?.attributes?.workflowStep }})
                    </p>
                </div>-->
                <div class="flex flex-col gap-4">
                    <TimelineTwo  :steps="completedActiveSteps" />
                </div>
            </template>
            <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
        </template>
        <UploadPop :application="application" type="application_fee"/>
    </PageContainer>
</template>
