<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import StepAction from '@/components/core/timelines/StepAction.vue';
import TimelineTwo from '@/components/core/timelines/TimelineTwo.vue';
import UploadPop from '@/components/shared/workflows/UploadPop.vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { useWorkflowSteps } from '@/composables/shared/useWorkflowSteps';
import { Audit } from '@/types/audit';
import { AuthObject } from '@/types/data-pagination';
import { Student, StudentApplication } from '@/types/students';
import { WorkflowStep } from '@/types/settings';
import { BreadcrumbItemInterface } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    application: StudentApplication;
    audit: Audit[];
}

const props = defineProps<Props>();
const { user } = props.auth;

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: user.attributes?.name, href: route('portal.dashboard') },
    { transChoiceKey: 'application', href: route('portal.applications') },
    { title: props.application?.attributes?.applicationTrackingNumber },
];

const workflowSteps = ref<WorkflowStep[]>([]);
const { isLoading, listWorkflowSteps, workflowSteps: loadedSteps } = useWorkflowSteps();
const { awaitTuitionPaymentProof, awaitApplicationPaymentProof } = useStudentApplications();

onMounted(async () => {
    await listWorkflowSteps();
    workflowSteps.value = loadedSteps.value ?? [];
});

const steps = computed(() => {
    if (!workflowSteps.value || currentStep.value == null) return [];
    return workflowSteps.value?.map(
        (step: WorkflowStep, index: number) =>
            <TimelineStep>{
                title: step.attributes?.name,
                description: step.attributes?.description,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${index + 1}`,
                status: getStepStatus(step),
                props: {
                    step,
                },
            },
    );
});

const completedActiveSteps = computed(() => {
    if (!workflowSteps.value || currentStep.value == null) return [];

    const currentPosition = Number(currentStep.value?.attributes?.position ?? 0);

    return workflowSteps.value
        .filter((step) => Number(step.attributes?.position) <= currentPosition)
        .map((step, index) => {
            return <TimelineStep>{
                title: step.attributes?.name,
                description: step.attributes?.description,
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

const currentStep = computed(() => {
    return props.application?.relationships?.workflowStep;
});

const paymentProofType = computed(() => {
    if (awaitApplicationPaymentProof(currentStep.value!)) {
        return 'application_fee';
    }
    if (awaitTuitionPaymentProof(currentStep.value!)) {
        return 'tuition_fee';
    }
    return 'other';
});

const getStepStatus = (step: WorkflowStep): string => {
    const currentPosition = Number(currentStep.value?.attributes?.position ?? 0);

    if (step.id === currentStep.value?.id) {
        return 'active';
    }

    if (Number(step.attributes?.position) < currentPosition) {
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
        <div class="my-6 flex flex-col" v-else>
            <template v-if="steps?.length > 0">
                <div class="flex flex-col gap-4">
                    <TimelineTwo :steps="completedActiveSteps" />
                </div>
            </template>
            <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
        </div>
        <UploadPop :application="application" :type="paymentProofType" />
    </PageContainer>
</template>
