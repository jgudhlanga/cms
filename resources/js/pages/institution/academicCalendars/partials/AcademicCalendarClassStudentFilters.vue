<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import type { AcademicCalendarClassStudentFiltersState } from '@/composables/academicCalendars/useAcademicCalendarClassStudentFilters';
import { IconName } from '@/enums/icons';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

interface Props {
    filters: AcademicCalendarClassStudentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    change: [filters: AcademicCalendarClassStudentFiltersState];
}>();

const name = ref(props.filters.name ?? '');
const search = ref(props.filters.search ?? '');
const genderSelection = ref<SelectOption | null>(null);

const genderOptions = computed<SelectOption[]>(() => [
    { value: 'male', label: trans_choice('general.male', 1) },
    { value: 'female', label: trans_choice('general.female', 1) },
    { value: 'unknown', label: 'Unknown' },
]);

const syncGenderSelectionFromFilters = (): void => {
    const gender = props.filters.gender ?? '';
    if (!gender) {
        genderSelection.value = null;
        return;
    }

    genderSelection.value = genderOptions.value.find((option) => String(option.value) === gender) ?? null;
};

syncGenderSelectionFromFilters();

watch(
    () => props.filters,
    () => {
        name.value = props.filters.name ?? '';
        search.value = props.filters.search ?? '';
        syncGenderSelectionFromFilters();
    },
    { deep: true },
);

const applyFilters = useDebounceFn(() => {
    const genderValue = genderSelection.value?.value ? String(genderSelection.value.value) : '';

    emit('change', {
        name: name.value || undefined,
        search: search.value || undefined,
        gender: genderValue as AcademicCalendarClassStudentFiltersState['gender'],
    });
}, 400);

watch([name, search, genderSelection], applyFilters, { deep: true });

const resetFilters = (): void => {
    name.value = '';
    search.value = '';
    genderSelection.value = null;
};
</script>

<template>
    <div class="mb-4 w-full max-w-full min-w-0">
        <div class="grid min-w-0 grid-cols-1 items-end gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:gap-4">
            <div class="min-w-0">
                <BaseInputWithIcon
                    v-model="name"
                    :icon="IconName.user"
                    full-width
                    :placeholder="$t('students.search_by_name_placeholder')"
                    class="w-full"
                />
            </div>
            <div class="min-w-0">
                <BaseInputWithIcon
                    v-model="search"
                    :icon="IconName.search"
                    full-width
                    :placeholder="$t('students.search_by_student_details_placeholder')"
                    class="w-full"
                />
            </div>
            <div class="min-w-0">
                <BaseCombobox
                    v-model="genderSelection"
                    :options="genderOptions"
                    :placeholder="$tChoice('trans.gender', 1)"
                    class="rounded-full"
                />
            </div>
            <div class="flex justify-end">
                <ResetButton @click="resetFilters" />
            </div>
        </div>
    </div>
</template>
