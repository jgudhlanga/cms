<script setup lang="ts">
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { CustomTab } from '@/types/utils';

defineProps<{
    tabs: CustomTab[];
    activeTab: string;
    badgeCounts?: Record<string, number | undefined>;
}>();

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const navButtonClass = (isActive: boolean): string =>
    cn(
        'inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm transition-colors -mb-px',
        isActive
            ? 'border-primary font-medium text-foreground'
            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
    );
</script>

<template>
    <nav
        class="flex w-full gap-0 overflow-x-auto border-b border-border"
        :aria-label="$t('trans.maintenance')"
    >
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :class="navButtonClass(activeTab === tab.value)"
            @click="emit('update:activeTab', tab.value)"
        >
            <component :is="icons[tab.icon!]" class="h-4 w-4 shrink-0" />
            <span>{{ tab.transLabel?.() }}</span>
            <span
                v-if="badgeCounts?.[tab.value] && badgeCounts[tab.value]! > 0"
                class="rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-medium text-destructive-foreground"
            >
                {{ badgeCounts[tab.value] }}
            </span>
        </button>
    </nav>
</template>
