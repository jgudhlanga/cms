<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseInput } from '@/components/core/form';
import IntakePeriodSelect from '@/components/core/form/select/IntakePeriodSelect.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { TextFieldType } from '@/enums/inputs';
import {
    ClassSizeEntry,
    DepartmentCourse,
    DepartmentCourseLevel,
    DepartmentIntakeClassSize,
    DepartmentIntakeClassSizeParams,
    DepartmentLevel,
} from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = department?.id?.toString() ?? '';
const courses = ref<DepartmentCourse[]>([]);
const levels = ref<DepartmentLevel[]>([]);
const classSizes = ref<DepartmentIntakeClassSize[]>([]);

const { loadDepartmentMetadata, isLoading, saveClassSizes } = useInstitutionDepartmentMetadata();
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();
const form = useForm<DepartmentIntakeClassSizeParams>({
    intake_period_id: null,
    class_sizes: [],
});
onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);
    classSizes.value = await loadDepartmentMetadata(route('v1.department-metadata.class-sizes', institutionDepartmentId));
    const coursesRes = await loadDepartmentMetadata(route('v1.department-metadata.courses', institutionDepartmentId));
    courses.value = coursesRes?.courses;
    const levelsRes = await loadDepartmentMetadata(route('v1.department-metadata.levels', institutionDepartmentId));
    levels.value = levelsRes?.levels;

    form.intake_period_id = intakePeriods.value?.data![0]?.id ?? null;
    fillClassSizeData();
});

const fillClassSizeData = () => {
    const filled: ClassSizeEntry[] = [];
    for (const course of courses.value) {
        for (const level of levels.value) {
            const existing = classSizes.value.find(
                (entry) =>
                    Number(entry.attributes.departmentCourseId) === Number(course.id) &&
                    Number(entry.attributes.departmentLevelId) === Number(level.id) &&
                    Number(entry.attributes.intakePeriodId) === Number(form.intake_period_id),
            );
            filled.push({
                department_course_id: Number(course?.id ?? ''),
                department_level_id: Number(level?.id ?? ''),
                class_size: existing ? existing.attributes.classSize : null,
            });
        }
    }
    form.class_sizes = filled;
}

const getEntry = (courseId: number, levelId: number): ClassSizeEntry | any => {
    return form.class_sizes.find((e) => e.department_course_id === courseId && e.department_level_id === levelId);
};
const submit = async () => {
    saveClassSizes(institutionDepartmentId, form);
};
const checkToDisable = (courseLevels: DepartmentCourseLevel[], levelId: number) => {
    return courseLevels.some((level: DepartmentCourseLevel) => Number(level.departmentLevelId) === levelId);
};

const handleSelectionChange = async (value: any) => {
    classSizes.value = await loadDepartmentMetadata(`api/v1/departments/${institutionDepartmentId}/class-sizes?intake_period=${value}&page_size=all`);
    fillClassSizeData();
};
</script>

<template>
    <div class="flex flex-col space-y-4">
        <div class="flex w-2/4 flex-col">
            <IntakePeriodSelect
                :loading="intakePeriodsLoading"
                :data="intakePeriods?.data ?? []"
                :label-uppercase="true"
                :vertical-layout="false"
                :is-multi="false"
                :is-searchable="true"
                :is-clearable="false"
                v-model="form.intake_period_id"
                @update:modelValue="handleSelectionChange"
            />
        </div>
        <div class="flex flex-col">
            <template v-if="isLoading || intakePeriodsLoading">
                <DataLoadingSpinner />
            </template>
            <template v-else>
                <form @submit.prevent="submit" v-if="courses.length > 0 && levels.length > 0">
                    <table class="hava-table my-4">
                        <thead class="hava-thead">
                            <tr>
                                <th class="hava-th" align="left">{{ $tChoice('trans.course', 1) }}</th>
                                <th class="hava-th" align="left" v-for="level in levels" :key="level.id">
                                    {{ level?.attributes?.level }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="hava-tbody">
                            <tr class="hava-tr" v-for="course in courses" :key="course.id">
                                <td class="hava-td">{{ course?.attributes?.course }}</td>
                                <td class="hava-td" v-for="level in levels" :key="level.id">
                                    <BaseInput
                                        :input-id="`class_size_${course?.id}_${level?.id}`"
                                        :type="TextFieldType.number"
                                        :placeholder="$t('trans.class_size')"
                                        v-model.number="getEntry(Number(course.id), Number(level.id)).class_size"
                                        :disabled="
                                            !checkToDisable(course?.relationships?.departmentCourseLevels ?? [], Number(level.id)) ||
                                            !form.intake_period_id
                                        "
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex items-center justify-center">
                        <BaseButton type="submit" :processing="form.processing">
                            {{ $t('trans.submit') }}
                        </BaseButton>
                    </div>
                </form>
                <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.courses_and_levels_not_found'   )" />
            </template>
        </div>
    </div>
</template>
