<script setup lang="ts">
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { buildPaginationPageLinks } from '@/lib/json-api';
import { PaginationLink, PaginationMeta } from '@/types/data-pagination';
import { computed } from 'vue';
import BaseSelect from '../form/select/BaseSelect.vue';

const props = defineProps<{ meta: PaginationMeta | null }>();

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
        .map(
            (row: PaginationLink) =>
                <any>{
                    value: row.label ? +row.label : null,
                    label: row.label,
                    active: row.active,
                },
        ),
);
</script>

<template>
    <div class="flex w-full items-center space-x-2">
        <ItemTitle :title="`${$t('trans.go_to_page')}:`" :uppercase="false" />
        <BaseSelect
            class="w-25"
            label=""
            v-bind="$attrs"
            placeholder=""
            :options="linksOptions"
            :is-searchable="false"
            :is-clearable="false"
            :vertical-layout="false"
        />
    </div>
</template>
