<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useHms } from '@/composables/hms/useHms';
import { Hostel } from '@/types/hms';
import { SelectOption } from '@/types/utils';
import { computed, onMounted, ref } from 'vue';

interface Props {
    isMulti?: boolean;
}

const props = defineProps<Props>();

const { fetchHostels } = useHms();
const hostels = ref<Hostel[]>([]);
const isLoading = ref<boolean>(false);

const loadHostels = async () => {
    isLoading.value = true;
    const res = await fetchHostels();
    if (res?.data) hostels.value = res.data;
    isLoading.value = false;
};

onMounted(() => loadHostels());

const options = computed(() => {
    return hostels.value?.map(
        (item: Hostel) =>
            <SelectOption>{
                value: Number(item.id?.toString() ?? ''),
                label: item?.attributes?.name,
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('hms.hostel', 1)"
        :placeholder="$t('hms.hostel_placeholder')"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options"
    />
</template>
