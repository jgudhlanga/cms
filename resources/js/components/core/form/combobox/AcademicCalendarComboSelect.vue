<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { AcademicCalendar } from '@/types/academic-calendar';

interface Props {
    data: AcademicCalendar[];
    form?: InertiaForm<any>;
}

const props = defineProps<Props>();

onMounted(async () => {});

const options = computed(() => {
    return props.data.length > 0
        ? props.data.map(
              (academicCalendar: AcademicCalendar) =>
                  <SelectOption>{
                      value: String(academicCalendar.id),
                      label: `${academicCalendar?.attributes?.name}`,
                  },
          )
        : [];
});
</script>

<template>
    <BaseCombobox :label="$tChoice('academic_calendar.academic_calendar', 1)" :options="options" v-bind="$attrs" />
</template>
