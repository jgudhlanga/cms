<script lang="ts" setup>
import { IconButton } from '@/components/core/button/index.js';
import BaseText from '@/components/core/util/BaseText.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { DepartmentApplicationStep } from '@/types/department-meta-data';

interface Props {
    step: DepartmentApplicationStep;
    action?: () => void;
}

defineProps<Props>();
const styles = 'font-extralight';
</script>

<template>
    <div class="mt-4 flex justify-between">
        <div class="flex flex-col space-y-2">
<!--            <div class="flex items-center space-x-2 text-xs">
                <component :is="icons[IconName.user]" class="size-4" />
                <BaseText :variant="ColorVariant.shade" :title="$tChoice('trans.staff', 1)" :classes="styles" />
                <div>{{ step?.relationships?.metadata?.staff?.join(', ') || '-&#45;&#45;' }}</div>
            </div>-->
            <div class="flex items-center space-x-2 text-xs">
                <component :is="icons[IconName.shield]" class="size-4" />
                <BaseText :variant="ColorVariant.shade" :title="$tChoice('trans.role', 2)" :classes="styles" />
                <div>{{ step?.relationships?.metadata?.roles?.join(', ') || '---' }}</div>
            </div>
            <div class="flex items-center space-x-2 text-xs">
                <component :is="icons[IconName.cogs]" class="size-4" />
                <BaseText :variant="ColorVariant.shade" :title="$tChoice('trans.action', 2)" :classes="styles" />
                <div>
                    {{ step?.relationships?.metadata?.actions?.map(a => `${a.title}`).join(', ') || '---'}}
                </div>
            </div>
        </div>
        <div class="flex justify-end">
            <BaseTooltip :content="$t('trans.edit_step')">
                <IconButton :variant="ColorVariant.primary_outline" :icon="IconName.edit" @click="() => (action ? action() : null)" />
            </BaseTooltip>
        </div>
    </div>
</template>
