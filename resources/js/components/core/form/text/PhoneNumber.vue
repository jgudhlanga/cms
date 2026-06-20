<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
    <BaseInput input-id="phone_number" :label="$t('trans.phone_number')" v-bind="$attrs" />
</template>
