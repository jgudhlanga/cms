<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useRaces } from '@/composables/shared/useRaces';
import { clearFormErrors } from '@/lib/forms';
import { Race } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}

const { isLoading, races, listRaces } = useRaces();
onMounted(async () => {
    await listRaces();
});
const props = defineProps<Props>();
const options = computed(() => {
    return races.value.map(
        (race: Race) =>
            <SelectOption>{
                value: Number(race.id),
                label: race?.attributes?.title,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'race');
    await listRaces(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.race', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
