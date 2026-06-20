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
    props.compact ? 'border border-border/60 bg-card shadow-sm' : 'bg-gray-50/50',
);

const contentClass = computed(() => (props.compact ? 'p-3' : 'p-4'));

const titleClass = computed(() =>
    props.compact ? 'text-xs font-medium text-muted-foreground' : 'text-sm text-gray-500',
);

const valueClass = computed(() =>
    props.compact ? 'text-xl leading-none font-semibold text-foreground' : 'text-2xl leading-none font-semibold text-gray-900',
);

const iconWrapperClass = computed(() =>
    props.compact ? `shrink-0 rounded-md p-1.5 ${props.accent}` : '',
);
</script>

<template>
    <Card :class="cardClass">
        <CardContent :class="contentClass">
            <div class="mb-1 flex items-center" :class="titleClass">
                <div v-if="compact" :class="iconWrapperClass">
                    <slot name="icon"></slot>
                </div>
                <template v-else>
                    <slot name="icon"></slot>
                </template>
                <span :class="compact ? 'ml-2 truncate' : 'ml-2'">{{ title }}</span>
            </div>
            <div class="mb-1" :class="valueClass">{{ value }}</div>
            <div
                class="text-xs"
                :class="{
                    'text-emerald-600': trend === 'up',
                    'text-rose-600': trend === 'down',
                    'text-amber-600': trend === 'warning',
                    'text-gray-500': !compact && (trend === 'neutral' || !trend),
                    'text-muted-foreground': compact && (trend === 'neutral' || !trend),
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
