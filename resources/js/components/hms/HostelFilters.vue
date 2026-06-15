<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { HostelFiltersState } from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { IconName } from '@/lib/icons';
import { trans } from 'laravel-vue-i18n';

const HOSTEL_TYPE_VALUES = ['all', 'male', 'female', 'mixed'] as const;
type HostelTypeValue = (typeof HOSTEL_TYPE_VALUES)[number];

interface Props {
    filters: HostelFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: HostelFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const wardenFilter = ref(props.filters.warden ?? '');

const roomTypeOptions = computed<SelectOption[]>(() => [
    { label: trans('hms.filter_all_types'), value: 'all' },
    { label: trans('hms.filter_girls_only'), value: 'female' },
    { label: trans('hms.filter_boys_only'), value: 'male' },
    { label: trans('hms.filter_mixed'), value: 'mixed' },
]);

/** vue3-select binds the option `value` string, not the full option object */
const typeSelection = ref<HostelTypeValue>('all');

const resolveTypeFilter = (): string | undefined => {
    const value = typeSelection.value;

    return value !== 'all' ? value : undefined;
};

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        type: resolveTypeFilter(),
        warden: wardenFilter.value || undefined,
    } as HostelFiltersState);
}, 400);

watch([search, typeSelection, wardenFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    typeSelection.value = 'all';
    wardenFilter.value = '';
};
</script>

<template>
    <div class="grid w-full grid-cols-1 gap-3 lg:grid-cols-4">
        <!-- Search -->
        <div class="col-span-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('hms.search_placeholder')"
                v-model="search"
                class="rounded-full"
            />
        </div>
        <!-- Warden filter -->
        <div class="col-span-1">
            <BaseInputWithIcon
                :icon="IconName.user_search"
                :placeholder="$t('hms.filter_warden')"
                v-model="wardenFilter"
                class="rounded-full"
            />
        </div>
        <!-- Gender Filter -->
        <div class="col-span-1">
            <BaseSelect
                :label="''"
                :placeholder="$t('hms.select_room_type')"
                v-model="typeSelection"
                :options="roomTypeOptions"
                :is-clearable="false"
            />
        </div>
        <div class="col-span-1">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>

