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
    data?: ModeOfStudy[];
    form?: InertiaForm<any>;
}

const { isLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const props = defineProps<Props>();

onMounted(async () => {
    if (props.data) {
        return;
    } else {
        await listModesOfStudy();
    }
});

const options = computed(() => {
    if (props.data && props.data.length) {
        return props.data.map(
            (mode: ModeOfStudy) =>
                <SelectOption>{
                    value: Number(mode.id),
                    label: mode?.attributes?.name,
                },
        );
    } else {
    }
    return modesOfStudy.value.map(
        (mode: ModeOfStudy) =>
            <SelectOption>{
                value: Number(mode.id),
                label: mode?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    if (props.form) {
        clearFormErrors(props.form, 'modeOfStudy');
    }
    if (!props.data?.length) {
        await listModesOfStudy(search);
    }
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.mode_of_study', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
