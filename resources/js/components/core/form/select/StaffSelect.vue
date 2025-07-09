<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useStaff } from '@/composables/institution/useStaff';
import { Staff } from '@/types/staff';
import { SelectOption } from '@/types/utils';
import { computed, onMounted } from 'vue';

interface Props {
    url: string;
    label?: string;
    placeholder?: string;
    isClearable?: boolean;
    isMulti?: boolean;
    isSearchable?: boolean;
    loading?: boolean;
    error?: string | object;
}

const props = defineProps<Props>();
const { url } = props;
const { isLoading, loadStaff, staff } = useStaff();

onMounted(async () => {
    await loadStaff(url);
});

const options = computed(() => {
    return staff?.value?.data?.map(
        (staff: Staff) =>
            <SelectOption>{
                value: Number(staff.id),
                label: staff?.relationships?.user?.attributes?.name ?? '',
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('trans.staff', 1)"
        :placeholder="placeholder"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options ?? []"
    />
</template>
