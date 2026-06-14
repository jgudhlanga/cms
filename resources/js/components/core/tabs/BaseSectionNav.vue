<script setup lang="ts">
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { CustomTab } from '@/types/utils';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        tabs: CustomTab[];
        activeTab: string;
        layout?: 'horizontal' | 'vertical';
        ariaLabel?: string;
        badgeCounts?: Record<string, number | undefined>;
    }>(),
    {
        layout: 'horizontal',
        ariaLabel: 'Section navigation',
    },
);

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const isHorizontal = computed(() => props.layout === 'horizontal');

const navClass = computed(() =>
    cn(
        'flex w-full gap-0',
        isHorizontal.value
            ? 'flex-row overflow-x-auto border-b border-border'
            : 'flex-col border-r border-border',
    ),
);

const navButtonClass = (isActive: boolean, isDisabled: boolean): string =>
    cn(
        'inline-flex items-center gap-2 text-sm transition-colors',
        isHorizontal.value
            ? 'shrink-0 border-b-2 px-4 py-2.5 -mb-px'
            : 'w-full border-l-2 px-4 py-2.5',
        isDisabled
            ? 'cursor-not-allowed opacity-50'
            : isActive
              ? isHorizontal.value
                  ? 'border-primary font-medium text-foreground'
                  : 'border-primary bg-muted/50 font-medium text-foreground'
              : isHorizontal.value
                ? 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                : 'border-transparent text-muted-foreground hover:bg-muted/30 hover:text-foreground',
    );

const onTabClick = (tab: CustomTab): void => {
    if (tab.disabled) {
        return;
    }

    emit('update:activeTab', tab.value);
};
</script>

<template>
    <nav class="min-w-0" :class="navClass" :aria-label="ariaLabel">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :disabled="tab.disabled"
            :class="navButtonClass(activeTab === tab.value, !!tab.disabled)"
            @click="onTabClick(tab)"
        >
            <component v-if="tab.icon" :is="icons[tab.icon]" class="h-4 w-4 shrink-0" />
            <span class="truncate uppercase">{{ tab.transLabel?.() }}</span>
            <span
                v-if="badgeCounts?.[tab.value] && badgeCounts[tab.value]! > 0"
                class="rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-medium text-destructive-foreground"
            >
                {{ badgeCounts[tab.value] }}
            </span>
        </button>
    </nav>
</template>
