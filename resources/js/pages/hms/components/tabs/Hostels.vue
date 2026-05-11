<script setup lang="ts">
import { Bed, DoorOpen, Users, Warehouse } from '@lucide/vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import HostelCard from '@/components/hms/HostelCard.vue';
import HostelStatsBadge from '@/components/hms/HostelStatsBadge.vue';
import HostelFilters from '@/components/hms/HostelFilters.vue';
import type { Hostel, HostelFiltersState } from '@/types/hms';
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


const { fetchHostels } = useHms();
const { hostelRefreshKey } = storeToRefs(useHmsStore());
const hostels = ref<Hostel[]>([]);
const filters = ref<HostelFiltersState>({});

const loadHostels = async (f: HostelFiltersState = {}) => {
    const res = await fetchHostels(f);
    if (res?.data) hostels.value = res.data;
};

onMounted(() => loadHostels());
watch(hostelRefreshKey, () => loadHostels(filters.value));
// ── Modal helpers ────────────────────────────────────────────────────────────
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

// ── Summary stats (computed from live data) ──────────────────────────────────
const hostelsList = computed<Hostel[]>(() => hostels.value);

const totalCapacity = computed(() =>
    hostelsList.value.reduce((sum, h) => sum + (h.attributes.capacity ?? 0), 0),
);
const totalRooms = computed(() =>
    hostelsList.value.reduce((sum, h) => sum + (h.attributes.roomsCount ?? 0), 0),
);
const totalOccupied = computed(() =>
    hostelsList.value.reduce((sum, h) => sum + (h.attributes.occupiedCount ?? 0), 0),
);
</script>

<template>
        <!-- ── Page header ───────────────────────────────────────────────── -->
        <div class="my-6 flex items-center justify-between">
            <!-- ── Summary stat badges ──────────────────────────────────────── -->
        <div class="mb-3 flex flex-wrap gap-3">
            <HostelStatsBadge
                :label="$t('hms.stat_blocks')"
                :value="String(hostelsList.length)"
                :icon="Warehouse"
                icon-class="text-indigo-500"
                value-class="text-indigo-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_total_capacity')"
                :value="String(totalCapacity)"
                :icon="Bed"
                icon-class="text-emerald-600"
                value-class="text-emerald-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_rooms')"
                :value="String(totalRooms)"
                :icon="DoorOpen"
                icon-class="text-amber-600"
                value-class="text-amber-700"
            />
            <HostelStatsBadge
                :label="$t('hms.stat_occupied_beds')"
                :value="String(totalOccupied)"
                :icon="Users"
                icon-class="text-rose-500"
                value-class="text-rose-600"
            />
        </div>

            <BaseButton id="hostel-create-btn" classes="rounded-full" :variant="ColorVariant.primary" @click="openCreate">
                <BaseIcon :name="IconName.add" />
                <span>{{ $t('hms.add_hostel') }}</span>
            </BaseButton>
        </div>

        <!-- ── Filters ───────────────────────────────────────────────────── -->
        <HostelFilters :filters="filters" @change="loadHostels" />

        <!-- ── Hostel cards grid ─────────────────────────────────────────── -->
        <div v-if="hostelsList.length" class="grid grid-cols-1 gap-3 md:grid-cols-3 ">
            <HostelCard
                v-for="hostel in hostelsList"
                :key="hostel.id"
                :hostel="hostel"
                @edit="openEdit"
                @delete="onDelete"
            />
        </div>

        <!-- ── Empty state ───────────────────────────────────────────────── -->
        <Empty v-else :message="$t('hms.no_hostels_found')" />
</template>