<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useAddressTypes } from '@/composables/addressTypes/useAddressTypes';
import { clearFormErrors } from '@/lib/forms';
import { AddressType } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}

const props = defineProps<Props>();
const { isLoading, listAddressTypes, addressTypes } = useAddressTypes();

onMounted(async () => {
    await listAddressTypes();
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'addressType');
    await listAddressTypes(search);
}, 600);

const options = computed(() => {
    return addressTypes.value.map(
        (addressType: AddressType) =>
            <SelectOption>{
                value: Number(addressType.id),
                label: addressType?.attributes?.title,
            },
    );
});
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.address_type', 1)"
        :options="options"
        :is-loading="isLoading"
        :on-search="async (search: string) => await whenSearch(search)"
        v-bind="$attrs"
    />
</template>
