<script setup lang="ts">
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import StatsCard from '@/pages/dashboard/partials/StatsCard.vue';
import { OLevelSubjectResult } from '@/types/enrolments';

interface Props {
    oLevelResults: OLevelSubjectResult[];
}
defineProps<Props>();
</script>

<template>
    <div class="flex w-full flex-col">
        <ComponentHeader header-title="O Levels" description="Your O-Level grades you provided" class="mb-3" />
        <div class="space-y-3">
            <StatsCard title="Provided O-Level Results" :value="oLevelResults.length" icon="checkDone" icon-bg-color="green" />
        </div>
        <div class="border-persian-500 text-persian-600 mt-3 flex w-full space-x-3 rounded-md border-l-4 bg-gray-50 p-3 shadow-sm">
            You can update your O-Level results by clicking <a :href="route('portal.manage-o-level-results')" class="underline">here</a>.
        </div>
    </div>
</template>
