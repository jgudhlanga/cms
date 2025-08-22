<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { clearFormErrors } from '@/lib/forms';
import { ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}

const { isLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const props = defineProps<Props>();

onMounted(async () => {
    await listModesOfStudy();
});

const options = computed(() => {
    return modesOfStudy.value.map(
        (mode: ModeOfStudy) =>
            <SelectOption>{
                value: Number(mode.id),
                label: mode?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'modeOfStudy');
    await listModesOfStudy(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.mode_of_study', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
