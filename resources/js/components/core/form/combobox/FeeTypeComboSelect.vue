<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useFeeTypes } from '@/composables/shared/useFeeTypes';
import { clearFormErrors } from '@/lib/forms';
import { FeeType } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, feeTypes, listFeeTypes } = useFeeTypes();
onMounted(async () => {
    await listFeeTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
    return feeTypes.value.map(
        (feeType: FeeType) =>
            <SelectOption>{
                value: Number(feeType.id),
                label: feeType?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'feeType');
    await listFeeTypes(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.fee_type', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
