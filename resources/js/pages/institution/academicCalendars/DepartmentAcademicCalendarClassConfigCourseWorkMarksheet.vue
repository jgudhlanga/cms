<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CourseWorkClassMarksheet from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkClassMarksheet.vue';
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
    canCreateCourseWork?: boolean;
    canUpdateCourseWork?: boolean;
    canExportCourseWork?: boolean;
    canImportCourseWork?: boolean;
}>();

const { department, academicCalendar, course, level, mode, classConfig, classConfigQuery } = toRefs(props);

const departmentClassesUrl = computed(() =>
    route('academic-calendars.department-classes', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
    }),
);

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
        { transChoiceKey: 'class', href: departmentClassesUrl.value },
        { title: String(classConfig.value.attributes?.calendarYear ?? academicCalendar.value.attributes.calendarYear) },
        { title: String(classConfig.value.attributes?.departmentCourse ?? course.value.attributes.course) },
        { transChoiceKey: 'academic_calendar.course_work_marksheet', transChoiceKeyIndex: 1 },
    ];
});

const courseWorkExportUrl = (moduleId: number, format: 'xlsx' | 'pdf', strict = false): string =>
    route('academic-calendars.department-classes.course-work-marksheet.export', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
        module: String(moduleId),
        format,
        ...(strict ? { strict: '1' } : {}),
    });
const courseWorkImportUrl = computed(() =>
    route('academic-calendars.department-classes.course-work-import', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
    }),
);
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_marksheet')" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentClassesUrl">
        <CourseWorkClassMarksheet
            :class-config-id="Number(classConfig.id)"
            :can-create="canCreateCourseWork"
            :can-update="canUpdateCourseWork"
            :can-export="canExportCourseWork"
            :can-import="canImportCourseWork"
            :course-work-export-url="courseWorkExportUrl"
            :course-work-import-url="courseWorkImportUrl"
        />
    </PageContainer>
</template>
