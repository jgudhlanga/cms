<script setup lang="ts">
import BaseInputNumber from './BaseInputNumber.vue';

interface Props {
    label: string;
    inputId: string;
}

defineProps<Props>();
</script>

<template>
    <BaseInputNumber class="" :input-id="inputId" v-bind="$attrs" :label="label" />
</template>
