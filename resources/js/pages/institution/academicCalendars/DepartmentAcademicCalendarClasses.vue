<script setup lang="ts">
import { Head, Link as InertiaLink, useForm } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { AcademicCalendar, AcademicCalendarClassGenerationContext, AcademicCalendarClassPreview, ClassConfig } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    academicCalendars: AcademicCalendar[];
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    auth: AuthObject;
    classConfig: ClassConfig;
    previewClasses: AcademicCalendarClassPreview[];
    generationContext: AcademicCalendarClassGenerationContext;
    errors: object;
}>();

const { department, level, course, mode, previewClasses, generationContext } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.departmentCode, href: route('institution-departments.show', String(department.id)) },
    { title: level.attributes.level, href: route('institution-departments.show', String(department.id)) },
    { title: course.attributes.course, href: route('institution-departments.show', String(department.id)) },
    { title: mode.attributes.name, href: route('institution-departments.show', String(department.id)) },
    { transChoiceKey: 'class' },
];

const form = useForm({
    class_config_id: generationContext.classConfigId,
    department_level_id: generationContext.departmentLevelId,
    department_course_id: generationContext.departmentCourseId,
    mode_of_study_id: generationContext.modeOfStudyId,
    students_per_class: generationContext.studentsPerClass,
});

const saveClasses = () => {
    form.post(
        route('academic-calendars.department-classes.store', {
            institution_department: String(department.id),
            academic_calendar: String(props.academicCalendar.id),
        }),
    );
};
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution-departments.show', String(department.id))">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="String(classConfig?.attributes?.academicCalendar ?? '---')">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <LabelValue :label="$tChoice('trans.course', 1)" :value="classConfig?.attributes?.departmentCourse ?? '---'" />
                    <LabelValue :label="$tChoice('trans.level', 1)" :value="classConfig?.attributes?.departmentLevel ?? '---'" />
                    <LabelValue :label="$tChoice('general.mode', 1)" :value="classConfig?.attributes?.modeOfStudy ?? '---'" />
                    <LabelValue :label="$tChoice('academic_calendar.class_unit_size', 1)" :value="String(classConfig?.attributes?.studentsPerClass ?? '---')" />
                </div>
            </BaseCard>

            <div class="flex items-center justify-between">
                <HeadingSmall :title="`${$t('enrolment.final_enrolments')} (${generationContext.finalStudentCount})`" />
                <BaseButton
                    :title="$t('enrolment.generate_classes')"
                    :disabled="previewClasses.length === 0 || form.processing"
                    :processing="form.processing"
                    @click="saveClasses"
                    classes="rounded-full"
                />
            </div>

            <BaseAlert
                v-if="previewClasses.length === 0"
                :title="$t('trans.no_data')"
                :description="$t('enrolment.no_preview_classes_generated')"
            />

            <template v-else>
                <BaseCard v-for="classPreview in previewClasses" :key="classPreview.name" :title="classPreview.name">
                    <div class="flex items-center justify-between gap-4">
                        <LabelValue :label="$tChoice('trans.student', 2)" :value="String(classPreview.studentCount)" /> 
                        <InertiaLink
                            v-if="classPreview.academicCalendarClassId"
                            :href="
                                route('academic-calendars.department-classes.show', {
                                    institution_department: String(department.id),
                                    academic_calendar: String(props.academicCalendar.id),
                                    academic_calendar_class: String(classPreview.academicCalendarClassId),
                                })
                            "
                        >
                        <BaseButton :title="$t('enrolment.view_class')" classes="rounded-full" :size="ButtonSize.sm" :variant="ColorVariant.success" />
                        </InertiaLink>
                        <BaseButton v-else :title="$t('enrolment.view_class')" :disabled="true" classes="rounded-full" :size="ButtonSize.sm" :variant="ColorVariant.success"/>
                    </div>
                </BaseCard>
            </template>
        </div>
    </PageContainer>
</template>
