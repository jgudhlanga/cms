<script setup lang="ts">
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PAGINATION_MAX_LIMIT } from '@/lib/constants';
import { computed } from 'vue';

const model = defineModel<number | string>({ required: true });

const pageSizeOptions = [
    { value: 5, label: '5' },
    { value: 10, label: '10' },
    { value: 15, label: '15' },
    { value: 20, label: '20' },
    { value: 50, label: '50' },
    { value: PAGINATION_MAX_LIMIT, label: 'All' },
] as const;

const selectValue = computed({
    get: (): string => String(model.value ?? 15),
    set: (value: string) => {
        model.value = Number(value);
    },
});

const onSelect = (value: string | number | bigint | Record<string, any> | null) => {
    if (value == null) {
        return;
    }
    selectValue.value = String(value);
};
</script>

<template>
    <div class="flex w-full items-center space-x-2">
        <ItemTitle :title="`${$t('trans.rows_per_page')}:`" :uppercase="false" />
        <Select :model-value="selectValue" @update:model-value="onSelect">
            <SelectTrigger class="h-9 w-30">
                <SelectValue placeholder="-" />
            </SelectTrigger>
            <SelectContent position="popper" :side-offset="4">
                <SelectItem
                    v-for="option in pageSizeOptions"
                    :key="option.value"
                    :value="String(option.value)"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
