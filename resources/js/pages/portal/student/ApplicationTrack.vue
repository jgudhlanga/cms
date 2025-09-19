<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import StepAction from '@/components/core/timelines/StepAction.vue';
import TimelineTwo from '@/components/core/timelines/TimelineTwo.vue';
import UploadPop from '@/components/shared/workflows/UploadPop.vue';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { Audit } from '@/types/audit';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Student, StudentProgram } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

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
    { title: user.attributes?.name, href: route('portal.dashboard') },
    { transChoiceKey: 'application', href: route('portal.applications') },
    { title: props.application?.attributes?.applicationTrackingNumber },
];

const workflowSteps = ref<DepartmentApplicationStep[]>([]);
const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();
const { awaitTuitionPaymentProof, awaitApplicationPaymentProof } = useStudentApplications();

onMounted(async () => {
    const data = await loadDepartmentMetadata(
        route('v1.department-metadata.workflow-steps', props.application?.attributes?.institutionDepartmentId?.toString()),
    );
    workflowSteps.value = data?.steps;
});

const auditSteps = computed(() => {
    return props.audit?.map((entry) => entry.attributes.properties.department_application_step_id).filter((id) => id !== undefined && id !== null);
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
                    step,
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


const currentStep = computed(() => {
    return props.application?.relationships?.departmentWorkflowStep;
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
                <div class="flex flex-col gap-4">
                    <TimelineTwo :steps="completedActiveSteps" />
                </div>
            </template>
            <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
        </template>
        <UploadPop :application="application" :type="paymentProofType" />
    </PageContainer>
</template>
