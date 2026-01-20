<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { ColorVariant } from '@/enums/colors';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { CourseLevelMode, DepartmentCourse, DepartmentCourseModeParams, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import BaseButton from '../../../../components/core/button/BaseButton.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentCourse: DepartmentCourse;
    departmentLevels: DepartmentLevel[];
    courseLevelModes: CourseLevelMode[];
    modesOfStudy: ModeOfStudy[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment, departmentCourse, departmentLevels, courseLevelModes } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', transChoiceKeyIndex: 2, href: route('institution-departments.index') },
    {
        title: institutionDepartment?.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment?.id?.toString() ?? '')),
    },
    {
        title: departmentCourse?.attributes.course,
        href: route('department-courses.show', getIdParams(departmentCourse?.id?.toString() ?? '')),
    },
    { title: 'Modes' },
];
const { navigateTo } = useUtils();

const form = useForm<DepartmentCourseModeParams>({
    department_course_id: String(departmentCourse?.id),
    mode_ids: {} as Record<string, (string | number)[]>,
});

const { saveCourseLevelModes } = useDepartmentCourses();

const buildModeMatrix = () => {
    const matrix: Record<string, (string | number)[]> = {};
    courseLevelModes.forEach((clm) => {
        const levelId = String(clm.attributes.departmentLevelId);
        matrix[levelId] = clm.relationships.modes.map((m) => m.id).filter((id): id is string => id !== undefined);
    });
    departmentLevels.forEach((level) => {
        const id = String(level.id);
        if (!matrix[id]) {
            matrix[id] = [];
        }
    });
    return matrix;
};

onMounted(() => {
    form.mode_ids = buildModeMatrix();
});
const updateCourse = () => {
    saveCourseLevelModes(departmentCourse?.id?.toString() ?? '', form, institutionDepartment?.attributes?.departmentId.toString() ?? '');
};
</script>

<template>
    <Head :title="`${$tChoice('trans.department', 1)} ${$tChoice('trans.course', 1)}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => updateCourse()" class="flex flex-col">
            <BaseCard :title="`${$t('trans.config')} ${$tChoice('trans.course', 1)} ${$tChoice('general.mode', 2)}`">
                <template v-if="departmentLevels && departmentLevels.length > 0">
                    <div class="grid grid-cols-1">
                        <template v-for="level in departmentLevels" :key="`level_key_${level['id']}`">
                            <div class="my-2 flex flex-col">
                                <HeadingSmall :title="level.attributes.level ?? ''" class="mb-2" />
                                <div class="grid grid-cols-1 gap-x-3 md:grid-cols-4" v-if="modesOfStudy && modesOfStudy.length > 0">
                                    <BaseCheckbox
                                        v-for="mode in modesOfStudy"
                                        :key="`course_mode_${level.id}_${mode.id}`"
                                        :input-id="`course_mode_id_${level.id}_${mode.id}`"
                                        :value="mode.id"
                                        v-model="form.mode_ids[String(level.id)]"
                                        :label="mode.attributes.name"
                                    />
                                </div>
                            </div>
                            <CustomSeparator />
                        </template>
                    </div>
                </template>
                <template v-else>
                    <Empty />
                </template>
            </BaseCard>
            <div class="flex items-center justify-center space-x-3 p-6">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    @click="
                        () =>
                            navigateTo(
                                route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? '')),
                            )
                    "
                    >{{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing">
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
