<script setup lang="ts">
import { missingDisplayTextClass } from '@/composables/students/studentProgrammeDisplay';

interface Props {
    labelKey: string;
    value: string;
}

defineProps<Props>();
</script>

<template>
    <span class="inline text-xs">
        <span class="text-muted-foreground">{{ $t(labelKey) }}:</span>
        <span
            class="ml-1 font-medium"
            :class="missingDisplayTextClass(value)"
        >
            <slot>{{ value }}</slot>
        </span>
    </span>
</template>
