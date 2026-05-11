<script setup lang="ts">
import { VenusAndMars } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { HostelFiltersState } from '@/types/hms';
import { IconName } from '@/lib/icons';
import { trans } from 'laravel-vue-i18n';

interface Props {
    filters: HostelFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: HostelFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const typeFilter = ref<string>('all');
const wardenFilter = ref(props.filters.warden ?? '');

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        warden: wardenFilter.value || undefined,
    } as HostelFiltersState);
}, 400);

watch([search, typeFilter, wardenFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    wardenFilter.value = '';
};

const roomTypeOptions = computed(() => [
    { label: trans('hms.filter_all_types'), value: 'all' },
    { label: trans('hms.filter_girls_only'), value: 'female' },
    { label: trans('hms.filter_boys_only'), value: 'male' },
    { label: trans('hms.filter_mixed'), value: 'mixed' },
]);
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
                v-model="typeFilter"
                :options="roomTypeOptions"
                :is-clearable="false"
            />
        </div>
        <div class="col-span-1">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>

