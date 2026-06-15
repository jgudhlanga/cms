<script setup lang="ts">
import { twMerge } from 'tailwind-merge';

interface Props {
    label?: string;
    value?: string;
    labelClasses?: string;
    valueClasses?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-0.5">
        <span :class="twMerge('text-[0.65rem] font-semibold tracking-[0.1em] text-muted-foreground uppercase', props.labelClasses)">
            {{ label }}
        </span>
        <span :class="twMerge('text-[0.85rem] font-bold tracking-[-0.01em] text-foreground', props.valueClasses)">
            {{ !value ? '---' : value }}
        </span>
    </div>
</template>
