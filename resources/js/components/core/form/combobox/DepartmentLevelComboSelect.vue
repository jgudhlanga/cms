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
    selectedLevelName?: string | null;
    triggerSearch?: boolean;
    label?: string;
}

const props = withDefaults(defineProps<Props>(), {
    triggerSearch: true,
    label: '',
});

const model = defineModel<SelectOption | null>();

const emit = defineEmits<{
    allowedLevelStatus: [{ available: boolean }];
}>();

const { isLoading, departmentLevels, listDepartmentLevels } = useDepartmentLevels();
const { isItTrue } = useUtils();

const normalizeLevelName = (name?: string | null) => name?.trim().toLowerCase() ?? '';

const toSelectOption = (item: DepartmentLevel): SelectOption => ({
    value: Number(item.id?.toString() ?? ''),
    label: item?.attributes?.level,
    relationshipOneValue: String(item.attributes.levelId),
});

onMounted(async () => {
    if (Number(props.institutionDepartmentId ?? '') > 0) {
        await listDepartmentLevels(props.institutionDepartmentId);
    }
});

const options = computed(() => {
    const selectedName = normalizeLevelName(props.selectedLevelName);

    return departmentLevels.value
        .filter((item: DepartmentLevel) => {
            if (!selectedName) {
                return true;
            }

            return normalizeLevelName(item.attributes.level) === selectedName;
        })
        .map(toSelectOption);
});

const isSameOption = (a: SelectOption | null | undefined, b: SelectOption | null | undefined) =>
    Number(a?.value) === Number(b?.value) && normalizeLevelName(a?.label) === normalizeLevelName(b?.label);

const syncAllowedLevel = () => {
    if (!normalizeLevelName(props.selectedLevelName)) {
        emit('allowedLevelStatus', { available: true });
        return;
    }

    if (!Number(props.institutionDepartmentId ?? '') || isLoading.value) {
        emit('allowedLevelStatus', { available: true });
        return;
    }

    const selectedName = normalizeLevelName(props.selectedLevelName);
    const match = departmentLevels.value.find((item) => normalizeLevelName(item.attributes.level) === selectedName);

    if (match) {
        const option = toSelectOption(match);
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

watch([options, isLoading, () => props.institutionDepartmentId, () => props.selectedLevelName], () => {
    syncAllowedLevel();
});

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
