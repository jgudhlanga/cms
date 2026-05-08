<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
    <BaseInput input-id="names" :label="$t('names')" v-bind="$attrs" />
</template>
