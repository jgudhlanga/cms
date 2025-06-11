<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { clearFormErrors } from '@/lib/forms';
import { DepartmentLevelCourse } from '@/types/department-meta-data';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, watch } from 'vue';

interface Props {
    form: InertiaForm<any>;
    departmentLevelId: string;
    triggerSearch?: boolean;
}

const { isLoading, levelCourses, listLevelCourses } = useDepartmentLevels();
const { isItTrue } = useUtils();
onMounted(async () => {
    if (props.departmentLevelId) {
        await listLevelCourses(props.departmentLevelId);
    }
});

const props = withDefaults(defineProps<Props>(), {
    triggerSearch: true,
});

const options = computed(() => {
    return levelCourses.value.map(
        (item: DepartmentLevelCourse) =>
            <SelectOption>{
                value: Number(item.departmentCourseId.toString() ?? ''),
                label: item?.course,
            },
    );
});

const placeholder = computed(() => {
    if (levelCourses.value.length > 0) {
        return trans('trans.select_one');
    } else {
        return trans('trans.select_dependency_description', { field: trans_choice('trans.level', 1).toLowerCase() });
    }
});

watch(
    () => props.departmentLevelId,
    async (newValue) => {
        clearFormErrors(props.form, 'course');
        if (isItTrue(props.triggerSearch) && Number(newValue) > 0) {
            await listLevelCourses(newValue?.toString() ?? '');
        }
    },
);
</script>

<template>
    <BaseCombobox :label="$tChoice('trans.course', 1)" :options="options" :placeholder="placeholder" :is-loading="isLoading" v-bind="$attrs" />
</template>
