<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { GenericButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import StepMetadata from '@/components/core/timelines/StepMetadata.vue';
import TimelineTwo from '@/components/core/timelines/TimelineTwo.vue';
import { useDepartmentWorkflows } from '@/composables/institution/useDepartmentWorkflows';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { getIdParams } from '@/lib/utils';
import LinkApplicationStepsToDepartment from '@/pages/institution/departments/partials/LinkApplicationStepsToDepartment.vue';
import StepActions from '@/pages/institution/departments/partials/StepActions.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment } = props;

const departmentApplicationSteps = ref<DepartmentApplicationStep[]>([]);

const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();
const { openDepartmentApplicationStepsModal, openDepartmentWorkflowActionModal } = useDepartmentWorkflows();

onMounted(async () => {
    const data = await loadDepartmentMetadata(route('v1.department-metadata.workflow-steps', props.institutionDepartment?.id?.toString()));
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

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    {
        title: institutionDepartment?.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    { transKey: 'application_workflow_steps' },
];
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col space-y-6">
            <div class="flex justify-end">
                <GenericButton
                    :icon="IconName.add"
                    class="cursor-pointer rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary"
                    @click="() => openDepartmentApplicationStepsModal(stepIds)"
                    :title="$t('trans.subscribe_to_application_steps')"
                />
            </div>
            <template v-if="isLoading">
                <DataLoadingSpinner />
            </template>
            <template v-else>
                <TimelineTwo v-if="steps?.length > 0" :steps="steps" />
                <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
            </template>
        </div>
        <LinkApplicationStepsToDepartment :institution-department-id="institutionDepartment.id?.toString() ?? ''" />
        <StepActions :institution-department-id="institutionDepartment.id?.toString() ?? ''" />
    </PageContainer>
</template>
