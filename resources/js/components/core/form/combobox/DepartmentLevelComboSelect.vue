<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import {
    filterDepartmentLevelOptions,
    findMatchingDepartmentLevelOption,
    normalizeLevelName,
} from '@/lib/departmentLevelComboOptions';
import { clearFormErrors } from '@/lib/forms';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

interface Props {
    form: InertiaForm<any>;
    institutionDepartmentId: string;
    selectedLevelName?: string | null;
    restrictToSelectedLevel?: boolean;
    triggerSearch?: boolean;
    label?: string;
}

const props = withDefaults(defineProps<Props>(), {
    restrictToSelectedLevel: true,
    triggerSearch: true,
    label: '',
});

const model = defineModel<SelectOption | null>();

const emit = defineEmits<{
    allowedLevelStatus: [{ available: boolean }];
}>();

const { isLoading, departmentLevels, listDepartmentLevels } = useDepartmentLevels();
const { isItTrue } = useUtils();

const isSameOption = (a: SelectOption | null | undefined, b: SelectOption | null | undefined) =>
    Number(a?.value) === Number(b?.value) && normalizeLevelName(a?.label) === normalizeLevelName(b?.label);

onMounted(async () => {
    if (Number(props.institutionDepartmentId ?? '') > 0) {
        await listDepartmentLevels(props.institutionDepartmentId);
    }
});

const options = computed(() =>
    filterDepartmentLevelOptions(
        departmentLevels.value,
        props.selectedLevelName,
        props.restrictToSelectedLevel,
    ),
);

const syncAllowedLevel = () => {
    if (!props.restrictToSelectedLevel) {
        emit('allowedLevelStatus', { available: true });
        return;
    }

    if (!normalizeLevelName(props.selectedLevelName)) {
        emit('allowedLevelStatus', { available: true });
        return;
    }

    if (!Number(props.institutionDepartmentId ?? '') || isLoading.value) {
        emit('allowedLevelStatus', { available: true });
        return;
    }

    const option = findMatchingDepartmentLevelOption(departmentLevels.value, props.selectedLevelName);

    if (option) {
        if (!isSameOption(model.value, option)) {
            model.value = option;
        }
        emit('allowedLevelStatus', { available: true });
        return;
    }

    if (model.value !== null) {
        model.value = null;
    }
    emit('allowedLevelStatus', { available: false });
};

watch(
    () => props.institutionDepartmentId,
    async (newValue) => {
        clearFormErrors(props.form, 'level');
        if (isItTrue(props.triggerSearch) && Number(props.institutionDepartmentId ?? '') > 0) {
            await listDepartmentLevels(newValue?.toString() ?? '');
        }
        syncAllowedLevel();
    },
);

watch(
    [options, isLoading, () => props.institutionDepartmentId, () => props.selectedLevelName, () => props.restrictToSelectedLevel],
    () => {
        syncAllowedLevel();
    },
);

const placeholder = computed(() => {
    if (departmentLevels.value.length > 0) {
        return trans('trans.select_one');
    }

    return trans('trans.select_dependency_description', { field: trans_choice('trans.department', 1).toLowerCase() });
});
</script>

<template>
    <BaseCombobox
        v-model="model"
        :label="label || $tChoice('trans.level', 1)"
        :options="options"
        :placeholder="placeholder"
        :is-loading="isLoading"
        v-bind="$attrs"
    />
</template>
