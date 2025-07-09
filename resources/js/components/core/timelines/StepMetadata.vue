<script lang="ts" setup>
import { IconButton } from '@/components/core/button/index.js';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import { IconName, icons } from '@/lib/icons';
import { TimelineStep } from '@/types/utils';

interface Props {
    step: TimelineStep;
    action?: () => void;
}

defineProps<Props>();
</script>

<template>
    <div class="mt-4 flex justify-between">
        <div class="flex flex-col space-y-2">
            <div class="flex space-x-2 text-xs">
                <component :is="icons[IconName.user]" class="size-4" />
                <div>James, Nicholas</div>
            </div>
            <div class="flex space-x-2 text-xs">
                <component :is="icons[IconName.shield]" class="size-4" />
                <div>Lecturer in charge, Head of Department</div>
            </div>
            <div class="flex space-x-2 text-xs">
                <component :is="icons[IconName.cogs]" class="size-4" />
                <div>Send Email,</div>
                <div>Notify applicant</div>
            </div>
        </div>
        <div class="flex justify-end">
            <BaseTooltip :content="$t('trans.edit_step')">
                <IconButton :icon="IconName.edit" @click="() => (action ? action() : null)" />
            </BaseTooltip>
        </div>
    </div>
</template>
