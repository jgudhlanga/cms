<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Student, StudentProgram } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { TimelineStep } from '@/types/utils';
import { trans_choice } from 'laravel-vue-i18n';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
    application: StudentProgram;
}

const props = defineProps<Props>();
const { user } = props.auth;

const breadcrumbs: BreadcrumbItemInterface[] = [
    { title: user.attributes?.name },
    { transKey: 'track_application' },
    { title: props.application?.attributes?.applicationTrackingNumber },
];
const applications = {
    status: 'Under Review',
};
const workflowSteps = ref<DepartmentApplicationStep[]>([]);
const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();

onMounted(async () => {
    const data = await loadDepartmentMetadata(
        route('v1.department-metadata.workflow-steps', props.application?.attributes?.institutionDepartmentId?.toString()),
    );
    workflowSteps.value = data?.steps;
});

// Example status-to-step mapping
const steps = ['Submitted', 'Under Review', 'Interview', 'Final Decision', 'Completed'];

const mySteps = computed(() => {
    return workflowSteps.value?.map(
        (step: DepartmentApplicationStep, index: number) =>
            <TimelineStep>{
                title: step.attributes?.workflowStep,
                description: step.attributes?.workflowStepDescription,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${index + 1}`,
                props: {
                    step,
                    action: () => {},
                },
            },
    );
});
// Current step index based on status
const currentStepIndex = computed(() => {
    const index = steps.findIndex((step) => step.toLowerCase() === applications.status.toLowerCase());
    return index >= 0 ? index : 0;
});

// Progress % for progress bar
const progressPercent = computed(() => {
    const total = steps.length;
    const index = currentStepIndex.value;
    return Math.round(((index + 1) / total) * 100);
});
</script>
<template>
    <Head :title="$tChoice('trans.application', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="isLoading">
            <DataLoadingSpinner />
        </template>
        <template v-else>
            <div>
                {{ mySteps }}
                <div class="h-6 w-full overflow-hidden rounded-full bg-gray-200">
                    <div class="bg-primary h-full transition-all duration-300" :style="{ width: progressPercent + '%' }"></div>
                </div>
                <p class="text-muted-foreground my-3 text-sm font-bold">Progress: {{ progressPercent }}% ({{ applications.status }})</p>
            </div>
        </template>
    </PageContainer>
</template>
