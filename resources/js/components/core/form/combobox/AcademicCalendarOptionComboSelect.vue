<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { AcademicCalendarOption } from '@/types/academic-calendar';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

interface Props {
    data: AcademicCalendarOption[];
    form?: InertiaForm<any>;
    showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: true,
});

onMounted(async () => {});

const options = computed(() => {
    return props.data.length > 0
        ? props.data.map(
              (academicCalendar: AcademicCalendarOption) =>
                  <SelectOption>{
                      value: String(academicCalendar.id),
                      label: `${academicCalendar?.attributes?.name}`,
                  },
          )
        : [];
});
</script>

<template>
    <BaseCombobox :label="showLabel ? $tChoice('trans.name', 1) : ''" :options="options" v-bind="$attrs" />
</template>
