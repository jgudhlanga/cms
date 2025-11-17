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
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { TimelineStep } from '@/types/utils';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';
import { hasAbility } from '@/lib/permissions';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const departmentId = department?.id?.toString() ?? '';

const departmentApplicationSteps = ref<DepartmentApplicationStep[]>([]);

const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();
const { openDepartmentApplicationStepsModal, openDepartmentWorkflowActionModal } = useDepartmentWorkflows();

onMounted(async () => {
    const data = await loadDepartmentMetadata(route('v1.department-metadata.workflow-steps', departmentId));
    departmentApplicationSteps.value = data?.steps;
});

const { modals } = useModalStore();

const modalOpen = ref(false);

watch(
  () => modals![APP_MODULE_KEYS.department_application_steps],
  async (isOpen) => {
    if (isOpen) {
      modalOpen.value = true;
    } else if (modalOpen.value) {
        const data = await loadDepartmentMetadata(route('v1.department-metadata.workflow-steps', departmentId));
        departmentApplicationSteps.value = data?.steps;
        modalOpen.value = false;
    }
  }
);

const metaDataModalOpen = ref(false);

watch(
  () => modals![APP_MODULE_KEYS.department_workflow_actions],
  async (isOpen) => {
    if (isOpen) {
      metaDataModalOpen.value = true;
    } else if (metaDataModalOpen.value) {
        const data = await loadDepartmentMetadata(route('v1.department-metadata.workflow-steps', departmentId));
        departmentApplicationSteps.value = data?.steps;
        metaDataModalOpen.value = false;
    }
  }
);

const steps = computed(() => {
    return departmentApplicationSteps.value?.map(
        (step: DepartmentApplicationStep, index: number) => <TimelineStep>{
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
    <div class="flex flex-col space-y-6">
        <div class="flex justify-end">
            <GenericButton
                v-if="hasAbility('department-setup:workflows')"
                :icon="IconName.add"
                class="cursor-pointer rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary_outline"
                @click="() => openDepartmentApplicationStepsModal(stepIds)"
                :title="$t('trans.subscribe_to_application_steps')"
            />
        </div>
        <template v-if="isLoading">
            <DataLoadingSpinner />
        </template>
        <template v-else>
            <TimelineTwo v-if="steps?.length > 0" :steps="steps ?? []" />
            <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
        </template>
    </div>
</template>
