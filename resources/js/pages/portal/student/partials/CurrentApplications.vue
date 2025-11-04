<script setup lang="ts">
import CustomCard from '@/components/core/card/CustomCard.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    applications: Enrolment[];
}
const props = defineProps<Props>();
const { applications } = props;
const message = computed(() => {
    if (applications.length === 1) {
        return `Great news! This is a confirmation that your application for ${applications[0].attributes?.level} - ${applications[0].attributes?.course} has been successfully submitted and awaiting processing`;
    }
    return 'Great news! This is a confirmation that your applications have been successfully submitted and now awaiting processing';
});

const statusMessage = (application: Enrolment) => {
    const step = application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? '';
    switch (step) {
        case 'Review':
            return 'Your application has been submitted and is awaiting review.';
        case 'Requirements':
            return 'Your application is currently under review by the admissions team. Please present the required documents (Academic certificates and transcripts, National ID, Birth certificate) at the Old Administration Block Boardroom, located in the Civil and Mechanical Engineering Section, during working hours';
        case 'Accepted':
            return 'Congratulations! Your application has been accepted.';
        case 'Rejected':
            return 'We regret to inform you that your application has been rejected.';
        case 'Waitlisted':
            return 'Due to the high number of qualified applicants this year, your name has been placed on the waiting list. This means that your admission is currently pending final placement confirmation.';
        case 'Enrolled':
            return 'We regret to inform you that your application has been rejected.';
        default:
            return 'Status information is currently unavailable.';
    }
};
</script>

<template>
    <div class="flex w-full flex-col" v-if="applications && applications.length > 0">
        <ComponentHeader header-title="Current applications" description="Overview of your applications" class="mb-3" />
        <div class="space-y-3">
            <!--            <BaseAlert :type="TypeVariant.success" :description="message" />-->
            <CustomCard v-for="(application, index) in applications" :key="application.id">
                <template #title>{{ `${String(index + 1)}.  ${application.attributes?.level}` }}</template>
                <!--                <template #header-buttons>
                    <span class="rounded-full bg-green-200 px-2 text-xs font-medium text-green-600">{{
                        application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? ''
                    }}</span>
                </template>-->
                <template #body>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <GridLabelValue :label="$tChoice('trans.department', 1)" :value="application.attributes?.department ?? ''" />
                        <GridLabelValue :label="$tChoice('trans.course', 1)" :value="application.attributes?.course ?? ''" />
                        <GridLabelValue label="Application staus" :value="application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? ''" />
                    </div>
                    <div class="flex flex-col pt-4">
                        <div class="text-xs text-primary">{{ statusMessage(application) }}</div>
                    </div>
                </template>
            </CustomCard>
        </div>
    </div>
</template>
