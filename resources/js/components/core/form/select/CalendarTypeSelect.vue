<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { computed, onMounted } from 'vue';

interface Props {
    url?: string;
    data?: IntakePeriod[];
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
const { isLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();

onMounted(async () => {
    if (!url) {
        return;
    } else {
        await listIntakePeriods(url);
    }
});

const options = computed(() => {
    if (props.data) {
        return props.data?.map(
            (period: IntakePeriod) =>
                <SelectOption>{
                    value: Number(period.id),
                    label: period?.attributes?.name,
                },
        );
    } else {
        return intakePeriods?.value?.data?.map(
            (period: IntakePeriod) =>
                <SelectOption>{
                    value: Number(period.id),
                    label: period?.attributes?.name,
                },
        );
    }
});
</script>

<template>
    <BaseSelect
        :label="''"
        :placeholder="''"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options"
    />
</template>
