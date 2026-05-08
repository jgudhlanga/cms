<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useTitles } from '@/composables/shared/useTitles';
import { clearFormErrors } from '@/lib/forms';
import { Title } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, titles, listTitles } = useTitles();
onMounted(async () => {
    await listTitles();
});
const props = defineProps<Props>();
const options = computed(() => {
    return titles.value.map(
        (title: Title) =>
            <SelectOption>{
                value: Number(title.id),
                label: title?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'title');
    await listTitles(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.title', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
