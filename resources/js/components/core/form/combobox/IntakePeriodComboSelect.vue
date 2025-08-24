<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { clearFormErrors } from '@/lib/forms';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    url?: string;
    data?: IntakePeriod[];
    form?: InertiaForm<any>;
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

const whenSearch = debounce(async (search: string) => {
    if(props.form) {
        clearFormErrors(props.form, 'intakePeriod');
    }
    if(url) {
        await listIntakePeriods(`${url}&search=${search}`);
    }
}, 600);

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
    <BaseCombobox
        :label="$tChoice('trans.intake_period', 1)"
        :options="options"
        :is-loading="isLoading"
        :on-search="async (search: string) => await whenSearch(search)"
        v-bind="$attrs"
    />
</template>
