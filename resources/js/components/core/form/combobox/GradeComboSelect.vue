<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { clearFormErrors } from '@/lib/forms';
import { Grade } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, grades, listGrades } = useGrades();
onMounted(async () => {
    await listGrades();
});
const props = defineProps<Props>();
const options = computed(() => {
    return grades.value.map(
        (grade: Grade) =>
            <SelectOption>{
                value: Number(grade.id),
                label: grade?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'grade');
    await listGrades(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.grade', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
