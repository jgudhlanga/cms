<script setup lang="ts">
import ResetButton from '@/components/core/button/ResetButton.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseInputWithIcon from '@/components/core/form/text/BaseInputWithIcon.vue';
import { ButtonSize } from '@/enums/buttons';
import { IconName } from '@/enums/icons';
import {
    activityDateRangeValue,
    defaultSearchableActivityTrailFilters,
    parseActivityDateRange,
    type ActivityTrailFiltersState,
} from '@/lib/activityTimeline';
import { resolveUiLabel } from '@/lib/uiLabel';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { trans } from 'laravel-vue-i18n';
import { computed, nextTick, ref, useSlots, watch } from 'vue';

const props = defineProps<{
    filters: ActivityTrailFiltersState;
    logNameOptions: SelectOption[];
}>();

const emit = defineEmits<{
    change: [filters: ActivityTrailFiltersState];
}>();

const search = ref(props.filters.search);
const logNameSelection = ref<SelectOption | null>(null);
const dateRange = ref<[string, string] | null>(activityDateRangeValue(props.filters.from, props.filters.to));
const isSyncingFromProps = ref(false);

const syncLogNameSelection = (): void => {
    const logName = props.filters.logName;

    if (!logName) {
        logNameSelection.value = null;

        return;
    }

    logNameSelection.value = props.logNameOptions.find((option) => String(option.value) === logName) ?? {
        value: logName,
        label: logName,
    };
};

syncLogNameSelection();

watch(
    () => [props.filters, props.logNameOptions],
    async () => {
        isSyncingFromProps.value = true;
        search.value = props.filters.search;
        dateRange.value = activityDateRangeValue(props.filters.from, props.filters.to);
        syncLogNameSelection();
        await nextTick();
        isSyncingFromProps.value = false;
    },
    { deep: true },
);

const currentFilters = (): ActivityTrailFiltersState => {
    const range = parseActivityDateRange(dateRange.value);

    return {
        event: props.filters.event,
        search: search.value.trim(),
        logName: logNameSelection.value?.value ? String(logNameSelection.value.value) : null,
        from: range.from,
        to: range.to,
    };
};

const emitChange = useDebounceFn(() => {
    if (isSyncingFromProps.value) {
        return;
    }

    emit('change', currentFilters());
}, 400);

watch([search, logNameSelection, dateRange], emitChange, { deep: true });

const resetFilters = (): void => {
    const defaults = defaultSearchableActivityTrailFilters();
    search.value = '';
    logNameSelection.value = null;
    dateRange.value = activityDateRangeValue(defaults.from, defaults.to);
};

const typePlaceholder = computed(() => resolveUiLabel('dashboard.activity_all_types', trans));
const slots = useSlots();
const hasLeading = computed(() => Boolean(slots.leading));
</script>

<template>
    <div class="w-full min-w-0">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div v-if="hasLeading" class="w-full min-w-0 sm:max-w-52 sm:flex-none lg:w-52">
                <slot name="leading" />
            </div>
            <div class="min-w-48 flex-1">
                <BaseInputWithIcon
                    v-model="search"
                    :icon="IconName.search"
                    full-width
                    :placeholder="$t('dashboard.activity_search_placeholder')"
                    class="w-full"
                />
            </div>
            <div class="w-full min-w-0 sm:w-48 sm:flex-none">
                <BaseCombobox v-model="logNameSelection" :options="logNameOptions" :placeholder="typePlaceholder" class="rounded-full" />
            </div>
            <div class="w-full min-w-0 sm:w-56 sm:flex-none">
                <BaseDatePicker
                    v-model="dateRange"
                    input-id="audit-trail-date-range"
                    :placeholder="$t('dashboard.activity_date_range')"
                    :enable-time-picker="false"
                    model-type="yyyy-MM-dd"
                    prevent-min-max-navigation
                    range
                />
            </div>
            <div class="flex shrink-0 flex-nowrap items-center justify-end gap-1.5">
                <ResetButton :size="ButtonSize.xs" @click="resetFilters" />
            </div>
        </div>
    </div>
</template>
