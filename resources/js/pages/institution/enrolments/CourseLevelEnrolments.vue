<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentApplicationStep, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Enrolment } from '@/types/enrolments';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { computed } from 'vue';
import { Link } from '@/types/ui';
import { useUtils } from '@/composables/core/useUtils';
import OLevelBased from './OLevelBased.vue';
import EclipseButton from '@/components/core/button/EclipseButton.vue';


interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    enrolments: Record<string, Enrolment[]>;
    workflowSteps: DepartmentApplicationStep[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { department, level, enrolments } = props;
const {isItTrue} = useUtils();

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { transChoiceKey: 'enrolment' },
];

const firstStepKey = Object.keys(enrolments)[0] ?? '';
const firstEnrolment = firstStepKey ? enrolments[firstStepKey]?.[0] : null;
const firstCourseName = firstEnrolment?.attributes?.course ?? '';

const sortedEnrolmentsByStep = computed(() => {
  const sorted: Record<string, Enrolment[]> = {};

  // Loop over each workflow step
  for (const [step, enrolmentArray] of Object.entries(enrolments)) {
    sorted[step] = [...enrolmentArray].sort((a, b) => {
      const totalA = a.relationships?.oLevelResults?.reduce(
        (sum, result) => sum + Number(result.attributes.gradePosition || 0),
        0
      ) ?? 0;

      const totalB = b.relationships?.oLevelResults?.reduce(
        (sum, result) => sum + Number(result.attributes.gradePosition || 0),
        0
      ) ?? 0;

      return totalA - totalB; // lowest total first
    });
  }

  return sorted;
});

const getNextSteps = (currentStepName: string) => {
  const currentStepObj = props.workflowSteps.find(
    (step: DepartmentApplicationStep) => step.attributes.workflowStep === currentStepName
  );

  if (!currentStepObj) {
    return []; // No matching step found
  }

  return props.workflowSteps.filter(
    (step: DepartmentApplicationStep) =>
      step.attributes.position > currentStepObj.attributes.position
  );
};

const levelRequirements = computed(() => {
    return level?.relationships?.requirement;
});


const buttonOptions = (currentStepName: string) => {
    const options = getNextSteps(currentStepName);
    let choices = [];
    for(const option of options) {
        choices.push({
            key: option.id,
            action: () => {},
            title: option?.attributes?.workflowStep
        })
    }
    return choices;
}

</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall v-if="firstCourseName" :title="firstCourseName" />
        <CustomSeparator classes="h-[1px] my-3" />
        <template v-if="sortedEnrolmentsByStep">
            <div v-for="(enrolmentsInStep, step) in sortedEnrolmentsByStep" :key="step" class="flex flex-col space-y-3">
                <div class="flex justify-between items-center mt-7">
		            <div class="flex mb-0.5 text-sm uppercase text-accent-foreground font-bold">{{ step }}</div>
                    <div class="flex space-x-2">
                       <EclipseButton
                       :options="buttonOptions(step)"
                        :group-title="$t('trans.send_all_applications_to')"
                        :show-group-icon="true"
                    />
                    </div>
                </div>
                <div class="inline-block min-w-full overflow-auto align-middle">
                    <template v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)">
                        <OLevelBased :enrolments="enrolmentsInStep" :level="level" :step-options="buttonOptions(step)"/>
                    </template>
                </div>
            </div>
        </template>
        <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_data_found_description', { data: $tChoice('trans.enrolment', 2) })"/>
    </PageContainer>
</template>
