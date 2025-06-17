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
    triggerSearch?: boolean;
}

const { isLoading, departmentLevels, listDepartmentLevels } = useDepartmentLevels();
const { isItTrue } = useUtils();
onMounted(async () => {
    if (props.institutionDepartmentId) {
        await listDepartmentLevels(props.institutionDepartmentId);
    }
});
const props = withDefaults(defineProps<Props>(), {
    triggerSearch: true,
});
const options = computed(() => {
    return departmentLevels.value.map(
        (item: DepartmentLevel) =>
            <SelectOption>{
                value: Number(item.attributes.levelId.toString() ?? ''),
                label: item?.attributes?.level,
            },
    );
});

watch(
    () => props.institutionDepartmentId,
    async (newValue) => {
        clearFormErrors(props.form, 'level');
        if (isItTrue(props.triggerSearch)) {
            await listDepartmentLevels(newValue?.toString() ?? '');
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
