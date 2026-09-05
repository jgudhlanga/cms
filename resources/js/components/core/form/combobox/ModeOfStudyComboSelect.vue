<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { clearFormErrors } from '@/lib/forms';
import { ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

interface Props {
    form?: InertiaForm<any>;
    departmentCourseId?: string;
    departmentLevelId?: string;
    institutionDepartmentId?: string;
    label?: string;
    /** When true, load modes from the application offerings catalogue. */
    useApplicationOfferings?: boolean;
    /** When true, include department/catalogue modes so a destination can be chosen before it is saved on the course. */
    includeCatalogueModes?: boolean;
}

const { isLoading, listCourseModesOfStudy, courseModesOfStudy, listModesOfStudy, listDepartmentModesOfStudy, modesOfStudy } =
    useModeOfStudy();
const props = withDefaults(defineProps<Props>(), {
    useApplicationOfferings: false,
    includeCatalogueModes: false,
});

const numericId = (value: string | number | null | undefined): number => {
    const id = Number(value ?? '');

    return Number.isFinite(id) && id > 0 ? id : 0;
};

const toModeOptions = (modes: ModeOfStudy[] | null | undefined): SelectOption[] =>
    (Array.isArray(modes) ? modes : []).map(
        (mode: ModeOfStudy) =>
            <SelectOption>{
                value: Number(mode.id),
                label: mode?.attributes?.name,
            },
    );

const courseLevelOptions = computed(() => toModeOptions(courseModesOfStudy.value));
const catalogueOptions = computed(() => toModeOptions(modesOfStudy.value));

const options = computed(() => {
    if (!props.includeCatalogueModes) {
        return courseLevelOptions.value.length > 0 ? courseLevelOptions.value : catalogueOptions.value;
    }

    const byId = new Map<number, SelectOption>();

    [...courseLevelOptions.value, ...catalogueOptions.value].forEach((option) => {
        byId.set(Number(option.value), option);
    });

    return [...byId.values()];
});

const placeholder = computed(() => {
    if (numericId(props.departmentCourseId) < 1) {
        return trans('trans.select_dependency_description', { field: trans_choice('trans.course', 1).toLowerCase() });
    }

    if (numericId(props.departmentLevelId) < 1) {
        return trans('trans.select_dependency_description', { field: trans_choice('trans.level', 1).toLowerCase() });
    }

    return trans('trans.select_one');
});

const loadModes = async (departmentCourseId: string, departmentLevelId: string, institutionDepartmentId: string): Promise<void> => {
    const courseId = numericId(departmentCourseId);
    const levelId = numericId(departmentLevelId);
    const departmentId = numericId(institutionDepartmentId);

    if (courseId > 0 && levelId > 0) {
        await listCourseModesOfStudy(String(courseId), String(levelId), props.useApplicationOfferings);
        if ((courseModesOfStudy.value ?? []).length > 0 && !props.includeCatalogueModes) {
            return;
        }
    } else {
        courseModesOfStudy.value = [];
    }

    if (props.includeCatalogueModes) {
        await listModesOfStudy();

        return;
    }

    if (departmentId > 0) {
        await listDepartmentModesOfStudy(String(departmentId));
        if ((modesOfStudy.value ?? []).length > 0) {
            return;
        }
    }

    await listModesOfStudy();
};

watch(
    () => [
        props.departmentCourseId,
        props.departmentLevelId,
        props.institutionDepartmentId,
        props.useApplicationOfferings,
        props.includeCatalogueModes,
    ],
    async ([courseId, levelId, departmentId]) => {
        if (props.form) {
            clearFormErrors(props.form, 'modeOfStudy');
        }
        await loadModes(String(courseId ?? ''), String(levelId ?? ''), String(departmentId ?? ''));
    },
    { immediate: true },
);
</script>

<template>
    <BaseCombobox
        :label="label ?? $tChoice('trans.mode_of_study', 1)"
        :options="options"
        :is-loading="isLoading"
        v-bind="$attrs"
        :placeholder="placeholder"
    />
</template>
