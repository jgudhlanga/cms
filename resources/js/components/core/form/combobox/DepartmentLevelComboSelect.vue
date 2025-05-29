<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { clearFormErrors } from '@/lib/forms';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';
import { useInstitution } from '@/composables/institution/useInstitution';

interface Props {
    form: InertiaForm<any>;
    institutionDepartmentId: string;
}

const { isLoading, departmentMetaData, loadDepartmentMetaData } = useInstitution();
onMounted(async () => {
    await loadDepartmentMetaData();
});
const props = defineProps<Props>();
const options = computed(() => {
    return departments.value.map(
        (institutionDepartment: InstitutionDepartment) =>
            <SelectOption>{
                value: Number(institutionDepartment?.attributes?.departmentId),
                label: institutionDepartment?.attributes?.department,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'country');
    await listDepartments(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.department', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
