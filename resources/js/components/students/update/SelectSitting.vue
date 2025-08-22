<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { EXAM_SITTINGS } from '@/lib/constants';
import { ref } from 'vue';

const options = ref(EXAM_SITTINGS);
</script>

<template>
    <BaseCombobox :label="''" :options="options" v-bind="$attrs" />
</template>
