<script setup lang="ts">
import Avatar from './Avatar.vue';

withDefaults(defineProps<{ label: string; isNumber?: boolean }>(), {
    isNumber: false,
});
</script>

<template>
    <Avatar src="" :name="label" :is-number="isNumber" />
</template>
