<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import StatsCard from '@/pages/dashboard/components/StatsCard.vue';
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
        <ComponentHeader header-title="O Levels" :description="$t('trans.ui_your_o_level_grades_you_provided')" class="mb-3" />
        <div class="space-y-3">
            <StatsCard :title="$t('trans.ui_provided_o_level_results')" :value="oLevelResults.length" icon="checkDone" icon-bg-color="green" />
        </div>
        <div
            v-if="!verificationMode"
            class="border-persian-500 text-persian-600 mt-3 flex w-full flex-col space-y-1 space-x-3 rounded-md border-l-4 bg-gray-50 p-3 shadow-sm"
        >
            <div>{{ $t('trans.ui_you_can_update_your_o_level_results_by_clicking_the_button_b') }}</div>
            <div class="flex flex-wrap">
                <BaseButton
                    @click="navigateTo(route('portal.manage-o-level-results'))"
                    :title="$tChoice('trans.result', 2)"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.xs"
                    class="rounded-full"
                />
            </div>
        </div>
    </div>
</template>
