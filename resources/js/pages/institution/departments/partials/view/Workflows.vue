<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { GenericButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import StepMetadata from '@/components/core/timelines/StepMetadata.vue';
import TimelineTwo from '@/components/core/timelines/TimelineTwo.vue';
import { useDepartmentWorkflows } from '@/composables/institution/useDepartmentWorkflows';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { TimelineStep } from '@/types/utils';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

const props = defineProps<Props>();
const institutionDepartment: InstitutionDepartment | null = ref(null);
const departmentApplicationSteps: DepartmentApplicationStep[] | [] = ref([]);

const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();
const { openDepartmentApplicationStepsModal, openDepartmentWorkflowActionModal } = useDepartmentWorkflows();

onMounted(async () => {
    const data = await loadDepartmentMetadata(route('v1.department-metadata.workflow-steps', props.institutionDepartmentId));
    institutionDepartment.value = data?.department;
    departmentApplicationSteps.value = data?.steps;
});

const steps = computed(() => {
    return departmentApplicationSteps.value?.map(
        (step: DepartmentApplicationStep, index: number) =>
            <TimelineStep>{
                title: step.attributes?.workflowStep,
                description: step.attributes?.workflowStepDescription,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${index + 1}`,
                component: StepMetadata,
                props: {
                    step,
                    action: () => openDepartmentWorkflowActionModal(step),
                },
            },
    );
});

const stepIds = computed(() => {
    return (departmentApplicationSteps.value ?? []).map((step: DepartmentApplicationStep) => step.attributes?.workflowStepId?.toString() ?? '');
});
</script>

<template>
    <div class="flex flex-col">
        <div class="flex justify-end mb-6" v-if="hasAbility('create:department-metadata')">
            <GenericButton
                :icon="IconName.add"
                class="cursor-pointer rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => openDepartmentApplicationStepsModal(stepIds)"
                :title="$t('trans.subscribe_to_application_steps')"
            />
        </div>
    </div>
    <DataLoadingSpinner v-if="isLoading" />
    <div v-else>
        <TimelineTwo v-if="steps?.length > 0" :steps="steps" />
        <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
    </div>
</template>
