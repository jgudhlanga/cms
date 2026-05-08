<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
    <BaseInput input-id="title" :label="$tChoice('trans.title', 1)" v-bind="$attrs" placeholder="" />
</template>
