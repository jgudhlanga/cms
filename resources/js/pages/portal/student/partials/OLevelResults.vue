<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import ComponentHeader from '@/pages/dashboard/partials/ComponentHeader.vue';
import StatsCard from '@/pages/dashboard/partials/StatsCard.vue';
import { OLevelSubjectResult } from '@/types/enrolments';

interface Props {
    oLevelResults: OLevelSubjectResult[];
}
defineProps<Props>();
const { navigateTo, isItTrue } = useUtils();
const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);
</script>

<template>
    <div class="flex w-full flex-col">
        <ComponentHeader header-title="O Levels" description="Your O-Level grades you provided" class="mb-3" />
        <div class="space-y-3">
            <StatsCard title="Provided O-Level Results" :value="oLevelResults.length" icon="checkDone" icon-bg-color="green" />
        </div>
        <div v-if="!verificationMode" class="flex w-full flex-col space-y-1 border-persian-500 text-persian-600 mt-3 space-x-3 rounded-md border-l-4 bg-gray-50 p-3 shadow-sm">
            <div>You can update your O-Level results by clicking the button below:</div>
            <div class="flex flex-wrap">
                <BaseButton
                    @click="navigateTo(route('portal.manage-o-level-results'))"
                    title="Results"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.xs"
                    class="rounded-full"
                />
            </div>
        </div>
    </div>
</template>
