<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useHms } from '@/composables/hms/useHms';
import { clearFormErrors } from '@/lib/forms';
import type { HostelRoom, HostelRoomFiltersState } from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    form: InertiaForm<any>;
    hostelId: string | number;
    label?: string;
}

const props = defineProps<Props>();

const { fetchRooms, isLoading } = useHms();
const rooms = ref<HostelRoom[]>([]);

async function loadAllRooms(search = ''): Promise<void> {
    const filters: HostelRoomFiltersState = {
        hostel: props.hostelId,
    };

    if (search.trim()) {
        filters.search = search.trim();
    }

    const items: HostelRoom[] = [];
    let result = await fetchRooms(filters);

    if (!result) {
        rooms.value = items;
        return;
    }

    items.push(...result.data);

    let next = result.links?.next ?? null;

    while (next) {
        result = await fetchRooms(filters, next);

        if (!result) {
            break;
        }

        items.push(...result.data);
        next = result.links?.next ?? null;
    }

    rooms.value = items;
}

onMounted(async () => {
    await loadAllRooms();
});

watch(
    () => props.hostelId,
    async () => {
        await loadAllRooms();
    },
);

const options = computed(() =>
    rooms.value.map(
        (item: HostelRoom) =>
            <SelectOption>{
                value: Number(item.id?.toString() ?? ''),
                label: item.attributes?.name ?? '',
            },
    ),
);

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'room');
    await loadAllRooms(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="label ?? $tChoice('hms.room', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
