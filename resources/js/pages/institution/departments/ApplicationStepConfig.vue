<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import TimelineOne from '@/components/core/timelines/TimelineOne.vue';
import { useDepartmentApplications } from '@/composables/institution/useDepartmentApplications';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { getIdParams } from '@/lib/utils';
import LinkApplicationStepsToDepartment from '@/pages/institution/departments/partials/LinkApplicationStepsToDepartment.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentApplicationSteps: DepartmentApplicationStep[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment, departmentApplicationSteps } = props;

const steps = computed(() => {
    return departmentApplicationSteps?.map(
        (step: DepartmentApplicationStep) =>
            <TimelineStep>{
                title: step.attributes?.workflowStep,
                description: step.attributes?.workflowStepDescription,
                timelineMarker: step.attributes?.position?.toString() ?? '',
                label: `${trans_choice('trans.step', 1)} ${step.attributes?.position}`,
            },
    );
});

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    {
        title: institutionDepartment.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment.id?.toString() ?? '')),
    },
    { transChoiceKey: 'step' },
];
const { openDepartmentApplicationStepsModal } = useDepartmentApplications();
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col space-y-4">
            <div class="flex justify-end">
                <GenericButton
                    :icon="IconName.add"
                    class="cursor-pointer rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary"
                    @click="() => openDepartmentApplicationStepsModal([])"
                    :title="$t('trans.subscribe_to_application_steps')"
                />
            </div>

        <TimelineOne v-if="steps?.length > 0" :steps="steps" />
        <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
        </div>
        <LinkApplicationStepsToDepartment :institution-department-id="institutionDepartment.id?.toString() ?? ''" />
    </PageContainer>
</template>
