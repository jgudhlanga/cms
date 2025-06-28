<script setup lang="ts">
import { TimelineStep } from '@/types/utils';

interface Props {
    steps: TimelineStep[];
}

defineProps<Props>();
</script>

<template>
    <div class="group relative py-6 pl-8 sm:pl-32" v-for="(step, index) in steps" :key="index">
        <!-- Primary label -->
        <div class="font-caveat text-primary mb-1 text-2xl font-medium sm:mb-0">{{ step.label }}</div>
        <!-- Vertical line (::before) ~ Date ~ Title ~ Circle marker (::after) -->
        <div
            class="after:bg-primary mb-1 flex flex-col items-start before:absolute before:left-2 before:h-full before:-translate-x-1/2 before:translate-y-3 before:self-start before:bg-slate-300 before:px-px group-last:before:hidden after:absolute after:left-2 after:box-content after:h-2 after:w-2 after:-translate-x-1/2 after:translate-y-1.5 after:rounded-full after:border-4 after:border-slate-50 sm:flex-row sm:before:left-0 sm:before:ml-[6.5rem] sm:after:left-0 sm:after:ml-[6.5rem]"
        >
            <span
                class="text-primary left-0 mb-1 inline-flex items-center justify-center rounded-full bg-blue-100 px-3 py-2 text-sm font-bold uppercase sm:absolute sm:mb-0"
            >
                {{ step.timelineMarker }}
            </span>
            <div class="text-accent-foreground text-lg font-bold">{{ step.title }}</div>
        </div>
        <!-- Content -->
        <div class="text-muted-foreground">{{ step.description }}</div>
    </div>
</template>
