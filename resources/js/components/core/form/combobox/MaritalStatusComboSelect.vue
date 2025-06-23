<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useMaritalStatuses } from '@/composables/shared/useMaritalStatuses';
import { clearFormErrors } from '@/lib/forms';
import { MaritalStatus } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}

const { isLoading, maritalStatuses, listMaritalStatuses } = useMaritalStatuses();
onMounted(async () => {
    await listMaritalStatuses();
});
const props = defineProps<Props>();
const options = computed(() => {
    return maritalStatuses.value.map(
        (maritalStatus: MaritalStatus) =>
            <SelectOption>{
                value: Number(maritalStatus.id),
                label: maritalStatus?.attributes?.title,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'maritalStatus');
    await listMaritalStatuses(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.marital_status', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
