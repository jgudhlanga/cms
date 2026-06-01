<script setup lang="ts">
import { useApplicationsByIntakePeriod } from '@/composables/students/useApplicationsByIntakePeriod';
import CurrentApplications from '@/pages/portal/student/partials/CurrentApplications.vue';
import type { Enrolment } from '@/types/enrolments';
import { toRef } from 'vue';

interface Props {
    applications: Enrolment[];
    compact?: boolean;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    showHeader: false,
});

const { groups, defaultOpenIntakeIds, intakeGroupDescription } = useApplicationsByIntakePeriod(toRef(props, 'applications'));
</script>

<template>
    <BaseAccordion
        v-if="applications.length > 0"
        class="w-full"
        :default-value="defaultOpenIntakeIds"
    >
        <BaseAccordionItem
            v-for="group in groups"
            :key="group.intakePeriodId"
            :value="group.intakePeriodId"
            :title="group.label"
            :description="intakeGroupDescription(group)"
        >
            <CurrentApplications
                :applications="group.applications"
                :compact="compact"
                :show-header="showHeader"
            />
        </BaseAccordionItem>
    </BaseAccordion>
</template>
