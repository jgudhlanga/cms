<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import CustomCard from '@/components/core/card/CustomCard.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { TypeVariant } from '@/enums/type-variants';
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
        return `Great news! This is a confirmation that your application for the ${applications[0].attributes?.course} has been successfully submitted`;
    }
    return 'Great news! This is a confirmation that your applications have been successfully submitted';
});
</script>

<template>
    <div class="flex w-full flex-col" v-if="applications && applications.length > 0">
        <ComponentHeader header-title="Current applications" description="Overview of your applications" class="mb-3" />
        <div class="space-y-3">
            <BaseAlert :type="TypeVariant.success" :description="message" />
            <CustomCard v-for="(application, index) in applications" :key="application.id">
                <template #title>{{ `${String(index + 1)}.  ${application.attributes?.level}` }}</template>
                <template #header-buttons>
                    <span class="rounded-full bg-green-200 px-2 text-xs font-medium text-green-600">{{
                        application?.relationships?.departmentWorkflowStep?.attributes?.workflowStep ?? ''
                    }}</span>
                </template>
                <template #body>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <GridLabelValue :label="$tChoice('trans.department', 1)" :value="application.attributes?.department ?? ''" />
                        <GridLabelValue :label="$tChoice('trans.course', 1)" :value="application.attributes?.course ?? ''" />
                    </div>
                </template>
            </CustomCard>
        </div>
    </div>
</template>
