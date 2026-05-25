<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { clearFormErrors } from '@/lib/forms';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    disallowedDepartments?: string[] | number[];
}

const { isLoading, departments, listDepartments } = useInstitutionDepartments();
onMounted(async () => {
    await listDepartments(route('v1.dropdowns.institution-departments', { is_academic: 1, page_size: 'all' }));
});
const props = defineProps<Props>();

const options = computed(() => {
    return departments.value?.data
        ?.filter((item: InstitutionDepartment) => {
            if (!props.disallowedDepartments?.length) return true;

            const departmentId = Number(item.attributes.departmentId);
            // ✅ exclude if departmentId is in disallowedDepartments
            return !props.disallowedDepartments.map(Number).includes(departmentId);
        })
        .map(
            (item: InstitutionDepartment) =>
                <SelectOption>{
                    value: Number(item.id?.toString() ?? ''),
                    label: item?.attributes?.department,
                },
        );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'department');
    await listDepartments(route('v1.dropdowns.institution-departments', { is_academic: 1, page_size: 'all', search }));
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
