<script setup lang="ts">
import { cn } from '@/lib/utils';
defineProps({
    otherClasses: { type: String, required: false },
});
</script>
<template>
    <div :class="cn('bg-accent flex flex-col rounded-xl dark:bg-transparent', otherClasses)">
        <slot name="body" />
    </div>
</template>
