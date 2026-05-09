<script setup lang="ts">
import { MapPin, RotateCw, Search, VenusAndMars } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { HostelFiltersState } from '@/types/hms';

interface Props {
    filters: HostelFiltersState;
    routeName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    routeName: 'hostels.index',
});

const search = ref(props.filters.search ?? '');
const typeFilter = ref<string>('all');
const locationFilter = ref<string>('all');

const applyFilters = useDebounceFn(() => {
    router.get(
        route(props.routeName),
        {
            search: search.value || undefined,
            type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 400);

watch([search, typeFilter, locationFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    locationFilter.value = 'all';
};
</script>

<template>
    <div class="sticky top-2 z-10 mb-8 rounded-2xl border border-slate-200/80 bg-white/70 p-4 shadow-sm backdrop-blur-sm">
        <div class="flex flex-col gap-3 lg:flex-row">
            <!-- Search -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    id="hostel-search"
                    v-model="search"
                    type="text"
                    :placeholder="$t('hms.search_placeholder')"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-300"
                />
            </div>

            <div class="flex flex-wrap gap-3">
                <!-- Type filter -->
                <div class="relative">
                    <VenusAndMars class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    <select
                        id="hostel-type-filter"
                        v-model="typeFilter"
                        class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-8 text-sm focus:ring-2 focus:ring-indigo-300"
                    >
                        <option value="all">{{ $t('hms.filter_all_types') }}</option>
                        <option value="female">{{ $t('hms.filter_girls_only') }}</option>
                        <option value="male">{{ $t('hms.filter_boys_only') }}</option>
                        <option value="mixed">{{ $t('hms.filter_mixed') }}</option>
                    </select>
                </div>

                <!-- Location filter (client-side) -->
                <div class="relative">
                    <MapPin class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                    <select
                        id="hostel-location-filter"
                        v-model="locationFilter"
                        class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-8 text-sm focus:ring-2 focus:ring-indigo-300"
                    >
                        <option value="all">{{ $t('hms.filter_all_locations') }}</option>
                    </select>
                </div>

                <!-- Reset -->
                <button
                    id="hostel-reset-filters"
                    class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600"
                    @click="resetFilters"
                >
                    <RotateCw class="h-3.5 w-3.5" /> {{ $t('hms.filter_reset') }}
                </button>
            </div>
        </div>
    </div>
</template>

