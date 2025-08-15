<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    classes?: string;
}

defineProps<Props>();
const defaultClasses = 'flex w-full h-[2px] bg-gray-600/20 my-2';
</script>

<template>
    <div :class="cn(defaultClasses, classes)"></div>
</template>
