<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useLevels } from '@/composables/institution/useLevels';
import { clearFormErrors } from '@/lib/forms';
import { Level } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form?: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, levels, listLevels } = useLevels();
onMounted(async () => {
    await listLevels();
});
const props = defineProps<Props>();
const options = computed(() => {
    return levels.value.map(
        (level: Level) =>
            <SelectOption>{
                value: Number(level.id),
                label: level?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    if (props.form) {
        clearFormErrors(props.form, 'level');
    }
    await listLevels(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.level', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
