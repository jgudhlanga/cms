<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { InertiaForm } from '@inertiajs/vue3';

interface Props {
    form: InertiaForm<any>;
}

defineProps<Props>();
</script>

<template>
    <BaseCombobox :label="$tChoice('trans.course', 1)" v-bind="$attrs" />
</template>
