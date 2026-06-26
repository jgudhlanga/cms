<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { getExamYearOptions, normalizeExamYear } from '@/lib/examYear';
import { SelectOption } from '@/types/utils';
import { computed } from 'vue';

interface Props {
    inputId: string;
    dateOfBirth?: string | null;
}

const props = defineProps<Props>();

const yearModel = defineModel<string | null>();

const options = computed(() => getExamYearOptions(props.dateOfBirth));

const comboboxModel = computed<SelectOption | null>({
    get() {
        const year = normalizeExamYear(yearModel.value);
        if (!year) {
            return null;
        }
        return options.value.find((option) => String(option.value) === year) ?? { value: year, label: year };
    },
    set(option) {
        yearModel.value = normalizeExamYear(option?.value ?? null);
    },
});
</script>

<template>
    <BaseCombobox :label="''" :options="options" :input-id="inputId" v-model="comboboxModel" v-bind="$attrs" />
</template>
