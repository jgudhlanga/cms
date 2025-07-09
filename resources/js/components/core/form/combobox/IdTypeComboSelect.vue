<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { clearFormErrors } from '@/lib/forms';
import { IdType } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}

const { isLoading, idTypes, listIdTypes } = useIdTypes();
onMounted(async () => {
    await listIdTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
    return idTypes.value.map(
        (type: IdType) =>
            <SelectOption>{
                value: Number(type.id),
                label: type?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'idType');
    await listIdTypes(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.id_type', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
