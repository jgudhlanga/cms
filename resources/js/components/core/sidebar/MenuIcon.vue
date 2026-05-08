<script setup lang="ts">
import { type Component } from 'vue';

defineProps<{
    icon?: Component;
}>();
</script>
<template>
    <component :is="icon" v-if="icon" />
</template>
