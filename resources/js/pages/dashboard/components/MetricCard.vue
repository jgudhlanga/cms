<script setup lang="ts">
import { Card, CardContent } from '@/Components/ui/card';

interface Props {
    title: string;
    value: string | number;
    subtext: string;
    trend?: 'up' | 'down' | 'neutral' | 'warning';
}

defineProps<Props>();
</script>

<template>
    <Card class="bg-gray-50/50">
        <CardContent class="p-4">
            <div class="mb-1 flex items-center text-sm text-gray-500">
                <slot name="icon"></slot>
                <span class="ml-2">{{ title }}</span>
            </div>
            <div class="mb-1 text-2xl leading-none font-semibold text-gray-900">{{ value }}</div>
            <div
                class="text-xs"
                :class="{
                    'text-emerald-600': trend === 'up',
                    'text-rose-600': trend === 'down',
                    'text-amber-600': trend === 'warning',
                    'text-gray-500': trend === 'neutral' || !trend,
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
