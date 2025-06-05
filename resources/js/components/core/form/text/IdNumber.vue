<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
    <BaseInput input-id="id_number" :label="$t('trans.id_number')" v-bind="$attrs" />
</template>
