<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { DepartmentDistributionSortKey } from '@/lib/departmentDistributionPresentation';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    search: string;
    sortKey: DepartmentDistributionSortKey;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:search': [value: string];
    'update:sortKey': [value: DepartmentDistributionSortKey];
}>();

const sortOptions: Array<{ value: DepartmentDistributionSortKey; labelKey: string }> = [
    { value: 'name_asc', labelKey: 'trans.ui_sort_department_az' },
    { value: 'total_desc', labelKey: 'trans.ui_sort_total_high_low' },
    { value: 'final_desc', labelKey: 'trans.ui_sort_final_high_low' },
    { value: 'rejection_desc', labelKey: 'trans.ui_sort_rejection_high_low' },
];

const legendItems = [
    { key: 'provisional', labelKey: 'trans.provisional', dotClass: 'bg-amber-400' },
    { key: 'waitlist', labelKey: 'trans.ui_waitlist', dotClass: 'bg-violet-400' },
    { key: 'rejected', labelKey: 'trans.ui_rejected', dotClass: 'bg-rose-400' },
    { key: 'verified', labelKey: 'trans.ui_verified', dotClass: 'bg-sky-400' },
    { key: 'final', labelKey: 'trans.ui_final', dotClass: 'bg-emerald-400' },
] as const;

const searchModel = computed({
    get: () => props.search,
    set: (value: string | number) => emit('update:search', String(value)),
});

const onSortChange = (value: string | number | bigint | Record<string, unknown> | null) => {
    if (typeof value !== 'string') {
        return;
    }
    if (!sortOptions.some((option) => option.value === value)) {
        return;
    }
    emit('update:sortKey', value as DepartmentDistributionSortKey);
};
</script>

<template>
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative w-full sm:max-w-[220px]">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="searchModel"
                    type="search"
                    class="h-8 pl-8 text-xs"
                    :placeholder="$t('trans.ui_search_department_placeholder')"
                    :aria-label="$t('trans.ui_search_department_placeholder')"
                />
            </div>
            <div class="flex items-center gap-1.5">
                <span class="shrink-0 text-[10px] font-medium text-muted-foreground">{{ $t('trans.ui_sort_by') }}</span>
                <Select :model-value="sortKey" @update:model-value="onSortChange">
                    <SelectTrigger class="h-8 w-[180px] text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent position="popper" :side-offset="4">
                        <SelectItem v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ $t(option.labelKey) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[10px] text-muted-foreground">
            <div v-for="item in legendItems" :key="item.key" class="inline-flex items-center gap-1">
                <span class="inline-block h-1.5 w-1.5 rounded-full" :class="item.dotClass" aria-hidden="true" />
                <span>{{ $t(item.labelKey) }}</span>
            </div>
        </div>
    </div>
</template>
