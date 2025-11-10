<script setup lang="ts">
import CustomCard from '@/components/core/card/CustomCard.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { useStudents } from '@/composables/students/useStudents';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import OfferLetterAnchor from '@/pages/portal/student/partials/OfferLetterAnchor.vue';
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
const { getApplicationStatus, hasOfferLetter, statusMessage } = useStudents();
</script>

<template>
    <div class="flex w-full flex-col" v-if="applications && applications.length > 0">
        <ComponentHeader header-title="Current applications" description="Overview of your applications" class="mb-3" />
        <div class="space-y-3">
            <!--            <BaseAlert :type="TypeVariant.success" :description="message" />-->
            <CustomCard v-for="(application, index) in applications" :key="application.id">
                <template #title>{{ `${String(index + 1)}.  ${application.attributes?.level}` }}</template>
                <!--                <template #header-buttons >

                </template>-->
                <template #body>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <GridLabelValue :label="$tChoice('trans.department', 1)" :value="application.attributes?.department ?? ''" />
                        <GridLabelValue :label="$tChoice('trans.course', 1)" :value="application.attributes?.course ?? ''" />
                        <GridLabelValue label="Application status" :value="getApplicationStatus(application) ?? ''" />
                    </div>
                    <div class="flex flex-col pt-4">
                        <div class="text-primary text-xs">{{ statusMessage(application) }}</div>
                        <div class="flex justify-end gap-2">
                            <OfferLetterAnchor v-if="hasOfferLetter(application)" :student-program-id="String(application.id)" />
                        </div>
                    </div>
                </template>
            </CustomCard>
        </div>
    </div>
</template>
