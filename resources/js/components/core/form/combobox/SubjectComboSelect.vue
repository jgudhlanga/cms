<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useSubjects } from '@/composables/institution/useSubjects';
import { clearFormErrors } from '@/lib/forms';
import { Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, subjects, listSubjects } = useSubjects();
onMounted(async () => {
    await listSubjects();
});
const props = defineProps<Props>();
const options = computed(() => {
    return subjects.value.map(
        (subject: Subject) =>
            <SelectOption>{
                value: Number(subject.id),
                label: subject?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'subject');
    await listSubjects(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.subject', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
