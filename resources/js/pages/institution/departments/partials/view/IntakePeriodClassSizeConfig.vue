<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseInput } from '@/components/core/form';
import IntakePeriodSelect from '@/components/core/form/select/IntakePeriodSelect.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useInstitutionDepartmentMetadata } from '@/composables/institution/useInstitutionDepartmentMetadata';
import { TextFieldType } from '@/enums/inputs';
import { ClassSizeEntry, DepartmentCourse, DepartmentIntakeClassSizeParams, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const courses = ref<DepartmentCourse[]>([]);
const levels = ref<DepartmentLevel[]>([]);

const { loadDepartmentMetadata, isLoading } = useInstitutionDepartmentMetadata();

const form = useForm<DepartmentIntakeClassSizeParams>({
    intake_period_id: null,
    class_sizes: [],
});
onMounted(async () => {
    const coursesRes = await loadDepartmentMetadata(route('v1.department-metadata.courses', props.department?.id?.toString()));
    courses.value = coursesRes?.courses;
    const levelsRes = await loadDepartmentMetadata(route('v1.department-metadata.levels', props.department?.id?.toString()));
    levels.value = levelsRes?.levels;
    const filled: ClassSizeEntry[] = [];
    for (const course of courses.value) {
        for (const level of levels.value) {
            filled.push({
                course_id: Number(course?.id ?? ''),
                level_id: Number(level?.id ?? ''),
                class_size: null,
            });
        }
    }
    form.class_sizes = filled;
});

const getEntry = (courseId: number, levelId: number): ClassSizeEntry | any => {
    return form.class_sizes.find((e) => e.course_id == courseId && e.level_id == levelId);
};
const submit = () => {
    console.log(form.data());
};
</script>

<template>
    <div class="flex flex-col space-y-4">
        <HeadingSmall :title="`${$tChoice('trans.intake_period', 2)} ${$t('trans.config')}`" :description="$t('trans.intake_config_description')" />
        <div class="flex w-1/4 flex-col">
            <IntakePeriodSelect
                :url="`api/v1/intake-periods?page_size=all`"
                :label-uppercase="true"
                :is-multi="false"
                :is-searchable="true"
                v-model="form.intake_period_id"
            />
        </div>
        <div class="flex flex-col">
            <template v-if="isLoading">
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
                                        :placeholder="$t('trans.enter_class_size')"
                                        v-model.number="getEntry(Number(course.id), Number(level.id)).class_size"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex justify-center items-center">
                        <BaseButton class="mt-4" type="submit"  :processing="form.processing">
                            {{ $t('trans.submit') }}
                        </BaseButton>
                    </div>
                </form>
                <BaseAlert v-else :description="$t('trans.courses_and_levels_not_found')" />
            </template>
        </div>
    </div>
</template>
