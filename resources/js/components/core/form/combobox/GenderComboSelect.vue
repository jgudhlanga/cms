<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useGenders } from '@/composables/shared/useGenders';
import { clearFormErrors } from '@/lib/forms';
import { Gender } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}
const { isLoading, genders, listGenders } = useGenders();
onMounted(async () => {
    await listGenders();
});
const props = defineProps<Props>();
const options = computed(() => {
    return genders.value.map(
        (gender: Gender) =>
            <SelectOption>{
                value: Number(gender.id),
                label: gender?.attributes?.title,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'gender');
    await listGenders(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.gender', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
