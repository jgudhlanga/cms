<script setup lang="ts">
interface Props {
    label: string;
}
defineProps<Props>();
</script>

<template>
    <label class="text-xs uppercase font-bold mb-2">{{ label }}</label>
</template>

