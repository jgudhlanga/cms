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
        variant?: 'pills' | 'underline';
        grouped?: boolean;
        description?: string;
        ariaLabel?: string;
        badgeCounts?: Record<string, number | undefined>;
    }>(),
    {
        layout: 'horizontal',
        variant: 'pills',
        grouped: true,
        ariaLabel: 'Section navigation',
    },
);

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const isHorizontal = computed(() => props.layout === 'horizontal');
const isPills = computed(() => props.variant === 'pills');

const navClass = computed(() =>
    cn(
        'flex w-full min-w-0',
        isPills.value
            ? isHorizontal.value
                ? 'flex-row gap-2 overflow-x-auto pb-1'
                : 'flex-col gap-1'
            : cn(
                  'gap-0',
                  isHorizontal.value
                      ? 'flex-row overflow-x-auto border-b border-border'
                      : 'flex-col border-r border-border',
              ),
    ),
);

const pillsContainerClass = computed(() => {
    if (!isPills.value) {
        return '';
    }

    if (!props.grouped) {
        return isHorizontal.value
            ? 'inline-flex w-fit min-w-0 flex-wrap items-center gap-2'
            : 'flex w-full flex-col gap-1';
    }

    return isPills.value && isHorizontal.value
        ? 'inline-flex w-fit min-w-0 items-center gap-0.5 rounded-lg bg-muted p-1'
        : isPills.value
          ? 'flex w-full flex-col gap-0.5 rounded-lg bg-muted p-1'
          : '';
});

const navButtonClass = (isActive: boolean, isDisabled: boolean): string => {
    if (isPills.value) {
        const standaloneClass = !props.grouped
            ? 'rounded-lg border border-border bg-card shadow-sm'
            : 'rounded-md';

        return cn(
            'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium transition-[color,box-shadow,background-color,border-color]',
            standaloneClass,
            isHorizontal.value ? 'shrink-0' : 'w-full justify-start',
            isDisabled
                ? 'cursor-not-allowed opacity-50'
                : isActive
                  ? props.grouped
                      ? 'bg-primary/10 text-primary shadow-sm'
                      : 'border-primary/30 bg-primary/10 text-primary shadow-sm'
                  : props.grouped
                    ? 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
                    : 'text-muted-foreground hover:border-border hover:bg-muted/40 hover:text-foreground',
        );
    }

    return cn(
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
};

const labelClass = (isActive: boolean): string =>
    cn('truncate', !isPills.value && 'uppercase', isPills.value && isActive && 'font-medium');

const onTabClick = (tab: CustomTab): void => {
    if (tab.disabled) {
        return;
    }

    emit('update:activeTab', tab.value);
};
</script>

<template>
    <div class="min-w-0 space-y-2">
        <nav :class="navClass" :aria-label="ariaLabel">
            <div :class="pillsContainerClass">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    :disabled="tab.disabled"
                    :class="navButtonClass(activeTab === tab.value, !!tab.disabled)"
                    @click="onTabClick(tab)"
                >
                    <component v-if="tab.icon" :is="icons[tab.icon]" class="h-4 w-4 shrink-0" />
                    <span :class="labelClass(activeTab === tab.value)">{{ tab.transLabel?.() }}</span>
                    <span
                        v-if="badgeCounts?.[tab.value] && badgeCounts[tab.value]! > 0"
                        class="rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-medium text-destructive-foreground"
                    >
                        {{ badgeCounts[tab.value] }}
                    </span>
                </button>
            </div>
        </nav>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
