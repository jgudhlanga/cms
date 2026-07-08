<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { Paginator } from '@/components/core/table';
import Empty from '@/components/core/util/Empty.vue';
import HostelCard from '@/components/hms/HostelCard.vue';
import HostelStatsBadge from '@/components/hms/HostelStatsBadge.vue';
import HostelFilters from '@/components/hms/HostelFilters.vue';
import type { Hostel, HostelFiltersState } from '@/types/hms';
import type { DataListProps } from '@/types/data-pagination';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { dangerDialog, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { storeToRefs } from 'pinia';
import { icons } from '@/lib/icons';

const { fetchHostels } = useHms();
const { hostelRefreshKey } = storeToRefs(useHmsStore());
const hostelsList = ref<DataListProps<Hostel>>({
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        total: 0,
        per_page: 0,
        current_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
        path: null,
        links: null,
    },
});
const filters = ref<HostelFiltersState>({});

const loadHostels = async (f: HostelFiltersState = filters.value) => {
    filters.value = f;
    const res = await fetchHostels(f);
    if (res) {
        hostelsList.value = res;
    }
};

const onFiltersChange = (f: HostelFiltersState) => {
    loadHostels(f);
};

const loadHostelsFromUrl = async (url: string) => {
    const res = await fetchHostels(filters.value, url);
    if (res) {
        hostelsList.value = res;
    }
};

onMounted(() => loadHostels());
watch(hostelRefreshKey, () => loadHostels(filters.value));

const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostels });
const openEdit = (hostel: Hostel) => openModal({ name: APP_MODULE_KEYS.hostels, edit: hostel });

const onDelete = (hostel: Hostel) => {
    dangerDialog(() => {
        router.delete(route('hostels.destroy', String(hostel.id)), {
            preserveScroll: true,
            onSuccess: () => successAlert(trans('hms.hostel_deleted')),
        });
        return true;
    });
};

const totalCapacity = computed(() =>
    hostelsList.value.data.reduce((sum, h) => sum + (h.attributes.capacity ?? 0), 0),
);
const totalRooms = computed(() =>
    hostelsList.value.data.reduce((sum, h) => sum + (h.attributes.roomsCount ?? 0), 0),
);
const totalOccupied = computed(() =>
    hostelsList.value.data.reduce((sum, h) => sum + (h.attributes.occupiedCount ?? 0), 0),
);
</script>

<template>
    <div class="my-6 flex items-center justify-between">
        <div class="mb-3 flex flex-wrap gap-3">
            <HostelStatsBadge
                :label="$t('hms.stat_blocks')"
                :value="String(hostelsList.meta.total || hostelsList.data.length)"
                :icon="icons[IconName.warehouse]"
                icon-class="text-indigo-500"
                value-class="text-indigo-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_total_capacity')"
                :value="String(totalCapacity)"
                :icon="icons[IconName.bed]"
                icon-class="text-emerald-600"
                value-class="text-emerald-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_rooms')"
                :value="String(totalRooms)"
                :icon="icons[IconName.room]"
                icon-class="text-amber-600"
                value-class="text-amber-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_occupied_beds')"
                :value="String(totalOccupied)"
                :icon="icons[IconName.users]"
                icon-class="text-rose-500"
                value-class="text-rose-600"
            />
        </div>

        <BaseButton id="hostel-create-btn" classes="rounded-full" :variant="ColorVariant.primary" @click="openCreate">
            <BaseIcon :name="IconName.add" />
            <span>{{ $t('hms.add_hostel') }}</span>
        </BaseButton>
    </div>

    <div class="mb-6 flex flex-col">
        <HostelFilters :filters="filters" @change="onFiltersChange" />
    </div>

    <div v-if="hostelsList.data.length" class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <HostelCard
            v-for="hostel in hostelsList.data"
            :key="hostel.id"
            :hostel="hostel"
            @edit="openEdit"
            @delete="onDelete"
        />
    </div>

    <Empty v-else :message="$t('hms.no_hostels_found')" />

    <Paginator
        v-if="(hostelsList.meta.last_page ?? 1) > 1"
        class="mt-6"
        :meta="hostelsList.meta"
        :use-api="true"
        :api-fetch-action="loadHostelsFromUrl"
    />
</template>
