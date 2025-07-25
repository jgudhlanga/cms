<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { computed, onMounted } from 'vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';

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
const {url} = props;
const { isLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();

onMounted(async () => {
    await listIntakePeriods(url);
});

const options = computed(() => {
    return intakePeriods?.value?.data?.map(
        (period: IntakePeriod) =>
            <SelectOption>{
                value: Number(period.id),
                label: period?.attributes?.name,
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('trans.intake_period', 1)"
        :placeholder="placeholder"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options" />
</template>
