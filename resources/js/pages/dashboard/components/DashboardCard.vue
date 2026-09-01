<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
    title: string;
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: true,
});

const headerClass = computed(() => (props.compact ? 'px-3 pt-2 pb-1' : 'px-4 pt-4 pb-2'));
const contentClass = computed(() => (props.compact ? 'px-3 pb-2' : 'px-4 pb-4'));
</script>

<template>
    <Card class="transition-shadow hover:shadow-md">
        <CardHeader :class="headerClass">
            <CardTitle class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent :class="contentClass">
            <slot></slot>
        </CardContent>
    </Card>
</template>
