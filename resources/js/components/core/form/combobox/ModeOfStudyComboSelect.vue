<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { clearFormErrors } from '@/lib/forms';
import { ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

interface Props {
    form?: InertiaForm<any>;
    departmentCourseId?: string;
    departmentLevelId?: string;
}

const { isLoading, listCourseModesOfStudy, courseModesOfStudy, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const props = defineProps<Props>();

onMounted(async () => {
    if (Number(props.departmentCourseId ?? '') > 0) {
        await listCourseModesOfStudy(String(props.departmentCourseId), String(props.departmentLevelId));
    } else {
        await listModesOfStudy();
    }
});

const options = computed(() => {
    if (Number(props.departmentCourseId ?? '') > 0) {
        return courseModesOfStudy.value?.map(
            (mode: ModeOfStudy) =>
                <SelectOption>{
                    value: Number(mode.id),
                    label: mode?.attributes?.name,
                },
        );
    } else {
        return modesOfStudy.value?.map(
            (mode: ModeOfStudy) =>
                <SelectOption>{
                    value: Number(mode.id),
                    label: mode?.attributes?.name,
                },
        );
    }
});
const placeholder = computed(() => {
    if (courseModesOfStudy.value && courseModesOfStudy.value.length > 0) {
        return trans('trans.select_one');
    } else {
        return trans('trans.select_dependency_description', { field: trans_choice('trans.course', 1).toLowerCase() });
    }
});

watch(
    () => props.departmentCourseId,
    async (newValue) => {
        clearFormErrors(props.form, 'modeOfStudy');
        if (Number(newValue) > 0) await listCourseModesOfStudy(String(newValue), String(props.departmentLevelId));
    },
);
</script>

<template>
    <BaseCombobox :label="$tChoice('trans.mode_of_study', 1)" :options="options" :is-loading="isLoading" v-bind="$attrs" :placeholder="placeholder" />
</template>
