<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { HostelRoomFiltersState } from '@/types/hms';
import { IconName } from '@/enums/icons';

interface Props {
    filters: HostelRoomFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: HostelRoomFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const hostelFilter = ref(props.filters.hostel ?? '');

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        hostel: hostelFilter.value || undefined,
    } as HostelRoomFiltersState);
}, 400);

watch([search, hostelFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    hostelFilter.value = '';
};
</script>

<template>
    <div class="flex w-full gap-3">
        <!-- Search -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('hms.search_room_placeholder')"
                v-model="search"
                class="w-full rounded-full"
            />
        </div>

        <!-- Hostel filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.warehouse"
                :placeholder="$t('hms.search_hostel_placeholder')"
                v-model="hostelFilter"
                class="rounded-full"
            />
        </div>
        <div class="flex flex-wrap">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>

