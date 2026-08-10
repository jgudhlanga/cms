<script setup lang="ts">
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { buildPaginationPageLinks } from '@/lib/json-api';
import { PaginationLink, PaginationMeta } from '@/types/data-pagination';
import { computed } from 'vue';

const props = defineProps<{ meta: PaginationMeta | null }>();
const model = defineModel<number | string>({ required: true });

const pageLinks = computed((): PaginationLink[] => {
    if (props.meta?.links?.length) {
        return props.meta.links;
    }

    const lastPage = props.meta?.last_page ?? 0;
    const currentPage = props.meta?.current_page ?? 1;

    return buildPaginationPageLinks(currentPage, lastPage);
});

const linksOptions = computed(() =>
    pageLinks.value
        .filter((row: PaginationLink) => Number(row.label) > 0)
        .map((row: PaginationLink) => ({
            value: Number(row.label),
            label: String(row.label ?? ''),
        }))
        .filter((row) => Number.isFinite(row.value) && row.value > 0),
);

const selectValue = computed({
    get: (): string => String(model.value ?? 1),
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
        <ItemTitle :title="`${$t('trans.go_to_page')}:`" :uppercase="false" />
        <Select :model-value="selectValue" @update:model-value="onSelect">
            <SelectTrigger class="h-9 w-20">
                <SelectValue placeholder="-" />
            </SelectTrigger>
            <SelectContent position="popper" :side-offset="4">
                <SelectItem
                    v-for="option in linksOptions"
                    :key="option.value"
                    :value="String(option.value)"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
