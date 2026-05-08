<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
    <BaseInput input-id="name" :label="$tChoice('trans.name', 1)" v-bind="$attrs" placeholder="" />
</template>
