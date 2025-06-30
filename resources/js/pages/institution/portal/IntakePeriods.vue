<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import TimelineOne from '@/components/core/timelines/TimelineOne.vue';
import type { Link } from '@/types/ui';
import { TimelineStep } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { GenericButton } from '@/components/core/button';

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', href: route('institution.index') },
    { transKey: 'portal_setup', href: route('portal.setup') },
    { transKey: 'application_workflows' },
];

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
    <Head :title="$t('trans.application_workflows')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex justify-end">
            <GenericButton
                :icon="IconName.cogs"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => {}"
                :title="$t('trans.link_workflow_steps')"
            />
        </div>
        <TimelineOne v-if="steps?.length > 0" :steps="steps" />
        <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_workflows_configured_description')" />
    </PageContainer>
</template>
