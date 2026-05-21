<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useGenders } from '@/composables/shared/useGenders';
import { useHms } from '@/composables/hms/useHms';
import { IconName } from '@/enums/icons';
import type { Hostel, HostelAllocationStatus, HostelAllocationType, HostelStudentFiltersState } from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { debounce } from 'lodash';
import { computed, onMounted, ref, watch } from 'vue';
import { trans } from 'laravel-vue-i18n';

interface Props {
    filters: HostelStudentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: HostelStudentFiltersState): void;
}>();

function unwrapList<T>(res: unknown): T[] {
    if (Array.isArray(res)) {
        return res as T[];
    }
    if (res && typeof res === 'object' && 'data' in res && Array.isArray((res as { data: T[] }).data)) {
        return (res as { data: T[] }).data;
    }
    return [];
}

const search = ref(props.filters.search ?? '');
const genderSelection = ref<SelectOption | null>(null);
const hostelSelection = ref<SelectOption | null>(null);
const roomFilter = ref(props.filters.room ?? '');
const typeSelection = ref<SelectOption | null>(null);
const statusSelection = ref<SelectOption | null>(null);

const { isLoading: gendersLoading, genders, listGenders } = useGenders();
const { fetchHostels, isLoading: hostelsLoading } = useHms();

const hostelOptions = ref<SelectOption[]>([]);

const genderOptions = computed<SelectOption[]>(() =>
    genders.value.map((g) => ({
        value: Number(g.id ?? 0),
        label: g.attributes?.title ?? '',
    })),
);

const typeOptions = computed<SelectOption[]>(() => [
    { value: 'direct', label: trans('hms.allocation_type_direct') },
    { value: 'apprentice', label: trans('hms.allocation_type_apprentice') },
    { value: 'guest', label: trans('hms.allocation_type_guest') },
    { value: 'other', label: trans('hms.allocation_type_other') },
]);

const statusOptions = computed<SelectOption[]>(() => [
    { value: 'active', label: trans('hms.allocation_status_active') },
    { value: 'closed', label: trans('hms.allocation_status_closed') },
    { value: 'pending', label: trans('hms.allocation_status_pending') },
]);

async function loadHostelsForFilter(q = ''): Promise<void> {
    const res = await fetchHostels(q.trim() ? { search: q.trim() } : {});
    const rows = unwrapList<Hostel>(res);
    hostelOptions.value = rows.map((h) => ({
        value: Number(h.id ?? 0),
        label: h.attributes?.name ?? '',
    }));
}

const whenHostelSearch = debounce(async (q: string) => {
    await loadHostelsForFilter(q);
}, 600);

const toOptionalSingleIdArray = (opt: SelectOption | null): number[] | undefined => {
    const id = Number(opt?.value ?? 0);
    return id > 0 ? [id] : undefined;
};

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        gender: toOptionalSingleIdArray(genderSelection.value),
        hostel: hostelSelection.value?.value ?? undefined,
        room: roomFilter.value || undefined,
        type: (typeSelection.value?.value as HostelAllocationType) || undefined,
        status: (statusSelection.value?.value as HostelAllocationStatus) || undefined,
    });
}, 400);

watch([search, genderSelection, hostelSelection, roomFilter, typeSelection, statusSelection], applyFilters, {
    deep: true,
});

const resetFilters = () => {
    search.value = '';
    genderSelection.value = null;
    hostelSelection.value = null;
    roomFilter.value = '';
    typeSelection.value = null;
    statusSelection.value = null;
};

onMounted(async () => {
    await Promise.all([listGenders(), loadHostelsForFilter()]);
});
</script>

<template>
    <div class="flex w-full min-w-0 flex-nowrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                full-width
                :placeholder="$t('hms.search_students_placeholder')"
                v-model="search"
                class="w-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="genderSelection"
                :options="genderOptions"
                :placeholder="$tChoice('trans.gender', 1)"
                :on-search="async (q: string) => await listGenders(q)"
                :is-loading="gendersLoading"
                class="w-full rounded-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="hostelSelection"
                :options="hostelOptions"
                :placeholder="$t('hms.hostel_placeholder')"
                :on-search="async (q: string) => await whenHostelSearch(q)"
                :is-loading="hostelsLoading"
                class="w-full rounded-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseInputWithIcon
                :icon="IconName.room"
                full-width
                :placeholder="$tChoice('hms.room', 1)"
                v-model="roomFilter"
                class="w-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="typeSelection"
                :options="typeOptions"
                :placeholder="$t('hms.filter_all_allocation_types')"
                class="w-full rounded-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="statusSelection"
                :options="statusOptions"
                :placeholder="$t('hms.filter_all_allocation_statuses')"
                class="w-full rounded-full"
            />
        </div>
        <div class="shrink-0">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>
