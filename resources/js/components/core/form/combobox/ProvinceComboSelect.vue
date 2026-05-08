<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useProvinces } from '@/composables/shared/useProvinces';
import { clearFormErrors } from '@/lib/forms';
import { Province } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}
const { isLoading, provinces, listProvinces } = useProvinces();
onMounted(async () => {
    await listProvinces();
});
const props = defineProps<Props>();
const options = computed(() => {
    return provinces.value.map(
        (province: Province) =>
            <SelectOption>{
                value: Number(province.id),
                label: province?.attributes?.title,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'province');
    await listProvinces(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.province', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
