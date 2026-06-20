<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { computed } from 'vue';

interface Props {
    title: string;
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
});

const headerClass = computed(() => (props.compact ? 'px-3 pt-3 pb-1' : 'px-4 pt-4 pb-2'));
const contentClass = computed(() => (props.compact ? 'px-3 pb-3' : 'px-4 pb-4'));
</script>

<template>
    <Card>
        <CardHeader :class="headerClass">
            <CardTitle class="text-xs font-medium tracking-wider text-gray-500 uppercase">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent :class="contentClass">
            <slot></slot>
        </CardContent>
    </Card>
</template>
