<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { SelectOption } from '@/types/utils';

interface Props {
    options: SelectOption[];
}

defineProps<Props>();
</script>

<template>
    <BaseCombobox :label="''" :options="options" v-bind="$attrs" />
</template>
