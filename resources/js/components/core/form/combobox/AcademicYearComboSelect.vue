<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { AcademicYear } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

interface Props {
    data: AcademicYear[];
    form?: InertiaForm<any>;
}

const props = defineProps<Props>();

onMounted(async () => {});

const options = computed(() => {
    return props.data.length > 0 ? props.data.map((mode: AcademicYear) => <SelectOption>{ value: String(mode.id), label: mode?.name }) : [];
});
</script>

<template>
    <BaseCombobox :label="$tChoice('trans.academic_year', 1)" :options="options" v-bind="$attrs" />
</template>
