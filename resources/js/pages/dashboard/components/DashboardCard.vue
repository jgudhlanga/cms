<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

interface Props {
    title: string;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader class="px-4 pt-4 pb-2">
            <CardTitle class="text-xs font-medium tracking-wider text-gray-500 uppercase">{{ title }}</CardTitle>
        </CardHeader>
        <CardContent class="px-4 pb-4">
            <slot></slot>
        </CardContent>
    </Card>
</template>
