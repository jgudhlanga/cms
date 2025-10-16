<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { InstitutionDepartment } from '@/types/institution';
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
const { isLoading, departments, listDepartments } = useInstitutionDepartments();

onMounted(async () => {
    await listDepartments(url);
});

const options = computed(() => {
    return departments?.value?.data?.map(
        (item: InstitutionDepartment) =>
            <SelectOption>{
                value: Number(item.id?.toString() ?? ''),
                label: item?.attributes?.department,
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('trans.department', 1)"
        :placeholder="placeholder"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options"
    />
</template>
