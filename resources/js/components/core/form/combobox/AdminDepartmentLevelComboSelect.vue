<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { clearFormErrors } from '@/lib/forms';
import { DepartmentLevel } from '@/types/department-meta-data';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

interface Props {
    form: InertiaForm<any>;
    institutionDepartmentId: string;
    allowedLevels?: string[] | number[];
    triggerSearch?: boolean;
}

const { isLoading, departmentLevels, listAdminDepartmentLevels } = useDepartmentLevels();
const { isItTrue } = useUtils();
onMounted(async () => {
    if (Number(props.institutionDepartmentId ?? '') > 0) {
        await listAdminDepartmentLevels(route('v1.dropdowns.institution-departments.levels', String(props.institutionDepartmentId)));
    }
});
const props = withDefaults(defineProps<Props>(), {
    triggerSearch: true,
});

const options = computed(() => {
    return departmentLevels.value
        .filter((item: DepartmentLevel) => {
            if (!props.allowedLevels?.length) return true;
            // normalize both to numbers for safe comparison
            const levelId = Number(item.attributes.levelId);
            return props.allowedLevels.map(Number).includes(levelId);
        })
        .map(
            (item: DepartmentLevel) =>
                <SelectOption>{
                    value: Number(item.id?.toString() ?? ''),
                    label: item?.attributes?.level,
                },
        );
});

watch(
    () => props.institutionDepartmentId,
    async (newValue) => {
        clearFormErrors(props.form, 'level');
        if (isItTrue(props.triggerSearch) && Number(props.institutionDepartmentId ?? '') > 0) {
            await listAdminDepartmentLevels(route('v1.dropdowns.institution-departments.levels', String(newValue)));
        }
    },
);

const placeholder = computed(() => {
    if (departmentLevels.value.length > 0) {
        return trans('trans.select_one');
    } else {
        return trans('trans.select_dependency_description', { field: trans_choice('trans.department', 1).toLowerCase() });
    }
});
</script>

<template>
    <BaseCombobox :label="$tChoice('trans.level', 1)" :options="options" :placeholder="placeholder" :is-loading="isLoading" v-bind="$attrs" />
</template>
