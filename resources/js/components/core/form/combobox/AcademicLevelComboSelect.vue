<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useAcademicLevels } from '@/composables/shared/useAcademicLevels';
import { clearFormErrors } from '@/lib/forms';
import { AcademicLevel } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
}

const props = defineProps<Props>();
const { isLoading, listAcademicLevels, academicLevels } = useAcademicLevels();

onMounted(async () => {
    await listAcademicLevels();
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'academicLevel');
    await listAcademicLevels(search);
}, 600);

const options = computed(() => {
    return academicLevels.value.map(
        (academicLevel: AcademicLevel) =>
            <SelectOption>{
                value: Number(academicLevel.id),
                label: academicLevel?.attributes?.name,
            },
    );
});
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.academic_level', 1)"
        :options="options"
        :is-loading="isLoading"
        :on-search="async (search: string) => await whenSearch(search)"
        v-bind="$attrs"
    />
</template>
