<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useHms } from '@/composables/hms/useHms';
import { clearFormErrors } from '@/lib/forms';
import type { Hostel } from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted, ref } from 'vue';

interface Props {
    form: InertiaForm<any>;
    label?: string;
}

const props = defineProps<Props>();

const { fetchHostels, isLoading } = useHms();
const hostels = ref<Hostel[]>([]);

const loadHostels = async (search = '') => {
    const result = await fetchHostels(search.trim() ? { search: search.trim() } : {});

    if (result?.data) {
        hostels.value = result.data;
    }
};

onMounted(async () => {
    await loadHostels();
});

const options = computed(() =>
    hostels.value.map(
        (item: Hostel) =>
            <SelectOption>{
                value: Number(item.id?.toString() ?? ''),
                label: item.attributes?.name ?? '',
            },
    ),
);

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'hostel');
    await loadHostels(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="label ?? $tChoice('hms.hostel', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
