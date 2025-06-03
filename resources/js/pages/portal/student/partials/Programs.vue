<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import { useInstitution } from '@/composables/institution/useInstitution';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { DepartmentLevel, DepartmentCourse } from '@/types/department-meta-data';
import { CreateApplicationParams } from '@/types/portal';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, watch } from 'vue';

const { department, level, course } = storeToRefs(useCreateApplicationFormStore());

interface Props {
    form: InertiaForm<CreateApplicationParams>;
}

defineProps<Props>();

const { loadDepartmentMetaData, departmentMetaData, isLoading } = useInstitution();

watch(department, async (newDepartment) => {
    await loadDepartmentMetaData(newDepartment?.value?.toString() ?? '');
    console.log(departmentMetaData.value);
});

const levels = computed(() => {
    return departmentMetaData?.value?.levels?.map((level: DepartmentLevel) => {
        return <SelectOption>{
            value: Number(level.attributes.levelId),
            label: level?.attributes?.level,
        };
    });
});

const courses = computed(() => {
    return departmentMetaData?.value?.courses?.filter((course: DepartmentCourse) => {
        return <SelectOption>{
            value: Number(course.attributes.courseId),
            label: course?.attributes?.course,
        };
    });
});

</script>

<template>
    <BaseCard>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="department"
                :error="form.errors.department"
                :label-uppercase="true"
                :is-required="true"
            />
            <DepartmentLevelComboSelect
                :form="form"
                v-model="level"
                :error="form.errors.level"
                :label-uppercase="true"
                :is-required="true"
                :options="levels"
                :is-loading="isLoading"
            />
            <DepartmentCourseComboSelect
                :form="form"
                v-model="course"
                :error="form.errors.course"
                :label-uppercase="true"
                :is-required="true"
                :options="courses"
                :is-loading="isLoading"
            />
        </div>
    </BaseCard>
</template>
