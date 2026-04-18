<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import type { AcademicCalendarClassMoveTarget } from '@/types/academic-calendar';
import type { SelectOption } from '@/types/utils';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        classes: AcademicCalendarClassMoveTarget[];
        currentClassId: number;
        institutionDepartmentId: number;
        calendarYear: string;
        widthClass?: string;
    }>(),
    {
        widthClass: 'max-w-md',
    },
);

const options = computed<SelectOption[]>(() =>
    props.classes.map((row) => ({
        value: row.id,
        label: row.name,
    })),
);

const selectedClass = ref<SelectOption | undefined>(undefined);

const syncSelectionFromProps = (): void => {
    const match = props.classes.find((row) => row.id === props.currentClassId);
    selectedClass.value = match ? { value: match.id, label: match.name } : undefined;
};

watch(
    () => [props.currentClassId, props.classes] as const,
    () => {
        syncSelectionFromProps();
    },
    { deep: true, immediate: true },
);

watch(selectedClass, (next) => {
    const selectedId = Number(next?.value ?? 0);
    if (selectedId <= 0 || selectedId === props.currentClassId) {
        return;
    }

    router.get(
        route('academic-calendars.department-classes.show', {
            institution_department: String(props.institutionDepartmentId),
            calendar_year: props.calendarYear,
            academic_calendar_class: String(selectedId),
        }),
    );
});
</script>

<template>
    <div v-if="classes.length > 1" class="flex">
        <BaseCombobox
            v-model="selectedClass"
            :label="$t('academic_calendar.change_class')"
            :options="options"
            :vertical-layout="false"
            :width-class="widthClass"
            :label-uppercase="true"
        />
    </div>
</template>
