<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { Relationship } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';
import { useRelationships } from '@/composables/shared/useRelationships';

interface Props {
    form: InertiaForm<any>;
}

const { isLoading, relationships, listRelationships } = useRelationships();
onMounted(async () => {
    await listRelationships();
});
const props = defineProps<Props>();
const options = computed(() => {
    return relationships.value.map(
        (relationship: Relationship) =>
            <SelectOption>{
                value: Number(relationship.id),
                label: relationship?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'relationship');
    await listRelationships(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.relationship', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
