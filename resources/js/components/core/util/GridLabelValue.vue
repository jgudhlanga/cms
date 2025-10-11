<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
}
defineProps<Props>();
</script>

<template>
    <div>
        <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">{{ label }}</p>
        <p class="mt-1">{{ value }}</p>
    </div>
</template>
