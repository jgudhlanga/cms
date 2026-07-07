<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CourseWorkImportPanel from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkImportPanel.vue';
import type { CourseWorkImportResult } from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkImportPanel.vue';
import type { AcademicCalendar, ClassConfig } from '@/types/academic-calendar';
import type { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import type { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed, toRefs } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    classConfig: ClassConfig;
    classConfigQuery: Record<string, string>;
    canImportCourseWork?: boolean;
    initialCourseWorkModuleId?: number | null;
    courseWorkImportResult?: CourseWorkImportResult | null;
}>();

const { department, academicCalendar, course, level, mode, classConfig, classConfigQuery } = toRefs(props);

const courseWorkMarksheetUrl = computed(() =>
    route('academic-calendars.department-classes.course-work-marksheet', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
    }),
);

const courseWorkImportTemplateUrl = (moduleId: number): string =>
    route('academic-calendars.department-classes.course-work-import.template', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
        module: String(moduleId),
    });

const courseWorkImportPreviewUrl = route('academic-calendars.department-classes.course-work-import.preview', {
    institution_department: String(department.value.id),
    calendar_year: String(academicCalendar.value.attributes.calendarYear),
    ...classConfigQuery.value,
});

const courseWorkImportProcessUrl = route('academic-calendars.department-classes.course-work-import.process', {
    institution_department: String(department.value.id),
    calendar_year: String(academicCalendar.value.attributes.calendarYear),
    ...classConfigQuery.value,
});

const breadcrumbs = computed<Array<Link>>(() => {
    const departmentShowUrl = route('institution-departments.show', String(department.value.id));

    return [
        { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
        {
            transChoiceKey: 'department',
            href: route('institution-departments.index', { is_academic: department.value.attributes?.isAcademic }),
        },
        { title: department.value.attributes.departmentCode, href: departmentShowUrl },
        { title: level.value.attributes.level, href: departmentShowUrl },
        { title: course.value.attributes.course, href: departmentShowUrl },
        { title: mode.value.attributes.name, href: departmentShowUrl },
        {
            transChoiceKey: 'class',
            href: route('academic-calendars.department-classes', {
                institution_department: String(department.value.id),
                calendar_year: String(academicCalendar.value.attributes.calendarYear),
                ...classConfigQuery.value,
            }),
        },
        { title: String(classConfig.value.attributes?.calendarYear ?? academicCalendar.value.attributes.calendarYear) },
        { title: String(classConfig.value.attributes?.departmentCourse ?? course.value.attributes.course) },
        { transChoiceKey: 'academic_calendar.course_work_import_title', transChoiceKeyIndex: 1 },
    ];
});
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_import_title')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="courseWorkMarksheetUrl">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold uppercase">{{ $tChoice('academic_calendar.course_work_import_title', 1) }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('academic_calendar.course_work_import_description') }}
                </p>
            </div>
        </template>
        <CourseWorkImportPanel
            :class-config-id="Number(classConfig.id)"
            :class-config-query="classConfigQuery"
            :department-id="Number(department.id)"
            :calendar-year="String(academicCalendar.attributes.calendarYear)"
            :can-import-course-work="canImportCourseWork ?? false"
            :initial-module-id="initialCourseWorkModuleId ?? null"
            :course-work-import-template-url="courseWorkImportTemplateUrl"
            :course-work-import-preview-url="courseWorkImportPreviewUrl"
            :course-work-import-process-url="courseWorkImportProcessUrl"
            :course-work-import-result="courseWorkImportResult"
        />
    </PageContainer>
</template>
