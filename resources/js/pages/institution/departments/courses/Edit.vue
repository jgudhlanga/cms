<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { BaseCheckbox } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { ButtonSize } from '@/enums/buttons';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentCourseLevel, DepartmentCourseUpdateParams, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseHero from './partials/CourseHero.vue';
import ProgrammeStructureCard from './partials/ProgrammeStructureCard.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentCourse: DepartmentCourse;
    departmentLevels: DepartmentLevel[];
    modesOfStudy: ModeOfStudy[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { institutionDepartment, departmentCourse, departmentLevels } = props;
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
];
const canToggleCourseworkCapture = hasAbility('toggle:coursework-capture');
const form = useForm<DepartmentCourseUpdateParams>({
    department_level_ids: departmentCourse?.relationships?.departmentCourseLevels?.map((item: DepartmentCourseLevel) => item?.departmentLevelId),
    ...(canToggleCourseworkCapture ? { coursework_capture_enabled: departmentCourse?.attributes?.courseworkCaptureEnabled !== false } : {}),
});

const isLevelSelected = (levelId: string | number | undefined) => (form.department_level_ids ?? []).includes(levelId);

const toggleLevel = (levelId: string | number | undefined) => {
    const current = form.department_level_ids ?? [];
    form.department_level_ids = isLevelSelected(levelId) ? current.filter((id) => id !== levelId) : [...current, levelId];
};

const allSelected = computed(() => (departmentLevels?.length ?? 0) > 0 && form.department_level_ids?.length === departmentLevels?.length);

const toggleSelectAll = () => {
    form.department_level_ids = allSelected.value ? [] : (departmentLevels?.map((item: DepartmentLevel) => item['id']) ?? []);
};

const { updateDepartmentCourses } = useDepartmentCourses();
const updateCourse = () => {
    updateDepartmentCourses(departmentCourse?.id?.toString() ?? '', form, institutionDepartment?.attributes?.departmentId.toString() ?? '');
};
</script>

<template>
    <Head :title="`${departmentCourse?.attributes.course} — ${institutionDepartment?.attributes.department}`" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? ''))"
    >
        <div class="space-y-4">
            <CourseHero
                :course="departmentCourse"
                :department="institutionDepartment"
                :selected-levels-count="form.department_level_ids?.length ?? 0"
                :total-levels-count="departmentLevels?.length ?? 0"
                :configured-structures-count="departmentCourse?.relationships?.departmentCourseLevels?.length ?? 0"
                :coursework-capture-enabled="form.coursework_capture_enabled"
                :show-coursework-capture="canToggleCourseworkCapture"
            />

            <form @submit.prevent="() => updateCourse()" class="flex w-full flex-col space-y-3 rounded-md border-l border-black p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <HeadingSmall :title="$t('trans.ui_course_configuration')" />
                    <BaseButton type="submit" :size="ButtonSize.sm" :processing="form.processing" :disabled="form.processing">
                        {{ $t('trans.save') }}
                    </BaseButton>
                </div>

                <template v-if="departmentLevels && departmentLevels.length > 0">
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                allSelected
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border text-muted-foreground hover:bg-muted/50 border-dashed'
                            "
                            @click="toggleSelectAll"
                        >
                            {{ $t('trans.select_all') }}
                        </button>
                        <button
                            v-for="level in departmentLevels"
                            :key="`level_key_${level['id']}`"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                isLevelSelected(level['id'])
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border text-muted-foreground hover:bg-muted/50'
                            "
                            @click="toggleLevel(level['id'])"
                        >
                            {{ level['attributes']['level'] }}
                        </button>
                    </div>
                </template>
                <template v-else>
                    <Empty />
                </template>

                <BaseCheckbox
                    v-if="canToggleCourseworkCapture"
                    input-id="coursework_capture_enabled"
                    v-model="form.coursework_capture_enabled"
                    :label="$t('trans.coursework_capture_enabled')"
                />
            </form>

            <div v-if="departmentCourse?.relationships?.departmentCourseLevels?.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <ProgrammeStructureCard
                    v-for="levelCourse in departmentCourse.relationships.departmentCourseLevels"
                    :key="`programme_structure_${levelCourse.id}`"
                    :level-course="levelCourse"
                />
            </div>
        </div>
    </PageContainer>
</template>
