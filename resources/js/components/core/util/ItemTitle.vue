<script setup lang="ts">
withDefaults(defineProps<{ title: string; uppercase?: boolean }>(), {
    uppercase: false,
});
</script>

<template>
    <div :class="`text-accent-foreground text-xs md:text-sm ${uppercase && 'uppercase'}`">{{ title }}</div>
</template>
