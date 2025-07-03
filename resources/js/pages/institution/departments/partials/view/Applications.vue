<script setup lang="ts">
import { TimelineStep } from '@/types/utils';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import TimelineOne from '@/components/core/timelines/TimelineOne.vue';

const steps: TimelineStep[] = [
    {
        title: 'Step one description',
        description: 'Step one action to be done',
        timelineMarker: '1',
        label: 'Step one',
    },
    {
        title: 'Step two description',
        description: 'Step two action to be done',
        timelineMarker: '2',
        label: 'Step two',
    },
    {
        title: 'Step three description',
        description: 'Step three action to be done',
        timelineMarker: '3',
        label: 'Step three',
    },
];
</script>

<template>
    <TimelineOne v-if="steps?.length > 0" :steps="steps" />
    <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
</template>
