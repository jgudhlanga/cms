<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
    title: string;
    value: string | number;
    subtext: string;
    trend?: 'up' | 'down' | 'neutral' | 'warning';
    compact?: boolean;
    accent?: string;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    accent: 'bg-muted text-muted-foreground',
});

const cardClass = computed(() =>
    props.compact
        ? 'border border-border/60 bg-card shadow-sm transition-shadow hover:shadow-md hover:border-border'
        : 'bg-muted/50 transition-shadow hover:shadow-sm',
);

const contentClass = computed(() => (props.compact ? 'p-2' : 'p-3.5'));

const titleClass = computed(() =>
    props.compact ? 'text-[11px] font-medium text-muted-foreground' : 'text-sm text-muted-foreground',
);

const valueClass = computed(() =>
    props.compact
        ? 'text-base leading-none font-semibold tabular-nums text-foreground'
        : 'text-xl leading-none font-semibold tabular-nums text-foreground',
);

const iconWrapperClass = computed(() =>
    props.compact ? `shrink-0 rounded-md p-1 ${props.accent}` : '',
);
</script>

<template>
    <Card :class="cardClass">
        <CardContent :class="contentClass">
            <div :class="[compact ? 'mb-0.5' : 'mb-1', 'flex items-center', titleClass]">
                <div v-if="compact" :class="iconWrapperClass">
                    <slot name="icon"></slot>
                </div>
                <template v-else>
                    <slot name="icon"></slot>
                </template>
                <span :class="compact ? 'ml-2 truncate' : 'ml-2'">{{ title }}</span>
            </div>
            <div :class="[compact ? 'mb-0.5' : 'mb-1', valueClass]">{{ value }}</div>
            <div
                class="text-xs"
                :class="{
                    'text-emerald-600': trend === 'up',
                    'text-rose-600': trend === 'down',
                    'text-amber-600': trend === 'warning',
                    'text-muted-foreground': trend === 'neutral' || !trend,
                }"
            >
                <div class="flex items-center">
                    <slot name="trendIcon"></slot>
                    <span :class="{ 'ml-1': !!$slots.trendIcon }">{{ subtext }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
