<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useReligions } from '@/composables/shared/useReligions';
import { clearFormErrors } from '@/lib/forms';
import { Religion } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}

const { isLoading, religions, listReligions } = useReligions();
onMounted(async () => {
    await listReligions();
});
const props = defineProps<Props>();
const options = computed(() => {
    return religions.value.map(
        (religion: Religion) =>
            <SelectOption>{
                value: Number(religion.id),
                label: religion?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'religion');
    await listReligions(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.religion', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
