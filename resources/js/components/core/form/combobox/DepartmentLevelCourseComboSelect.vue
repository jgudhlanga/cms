<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { DepartmentLevelCourse } from '@/types/department-meta-data';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    form: InertiaForm<any>;
    institutionDepartmentId?: string | number;
}

const props = defineProps<Props>();

const isLoading = ref(false);
const levelCourses = ref<DepartmentLevelCourse[]>([]);

const listDepartmentLevelCourses = async (institutionDepartmentId: string | number) => {
    if (Number(institutionDepartmentId ?? 0) <= 0) {
        levelCourses.value = [];
        return;
    }

    try {
        isLoading.value = true;
        levelCourses.value = await HttpService.get(route('v1.department-level-courses.by-institution-department', institutionDepartmentId));
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    await listDepartmentLevelCourses(props.institutionDepartmentId ?? '');
});

watch(
    () => props.institutionDepartmentId,
    async (nextInstitutionDepartmentId) => {
        clearFormErrors(props.form, 'department_level_course_id');
        await listDepartmentLevelCourses(nextInstitutionDepartmentId ?? '');
    },
);

const options = computed(() => {
    return levelCourses.value.map(
        (item: DepartmentLevelCourse) =>
            <SelectOption>{
                value: Number(item.id),
                label: `${item.level} - ${item.course}`,
            },
    );
});

const placeholder = computed(() => {
    if (levelCourses.value.length > 0) {
        return trans('trans.select_one');
    }

    return trans('trans.select_dependency_description', { field: trans_choice('trans.department', 1).toLowerCase() });
});
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.course', 1)"
        :options="options"
        :is-loading="isLoading"
        :placeholder="placeholder"
        v-bind="$attrs"
    />
</template>
