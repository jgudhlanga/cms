<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CourseWorkClassMarksheet from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkClassMarksheet.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface ClassDetail {
    id: number;
    name: string;
    modules: Array<{ id: number; title: string; code: string }>;
}

interface Props {
    classDetail: ClassDetail;
    module: { id: number; title: string; code: string };
    allowedModuleIds: number[];
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canCreateCourseWork: boolean;
    canUpdateCourseWork: boolean;
    canExportCourseWork: boolean;
    canImportCourseWork: boolean;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.class', 2), href: route('teaching.classes.index') },
    { title: props.classDetail.name, href: route('teaching.classes.show', props.classDetail.id) },
    { title: props.module.code || props.module.title },
]);

const courseWorkExportUrl = (moduleId: number, format: 'xlsx' | 'pdf', strict = false): string =>
    route('teaching.classes.marksheet.export', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: moduleId,
        format,
        ...(strict ? { strict: '1' } : {}),
    });

const courseWorkImportUrl = (moduleId: number): string =>
    route('teaching.classes.import', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: moduleId,
    });

const studentCourseWorkUrl = (studentEnrolmentId: number): string =>
    route('teaching.classes.student-course-work', {
        academic_calendar_class: props.classDetail.id,
        student_enrolment: studentEnrolmentId,
    });
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_marksheet')" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('teaching.classes.show', classDetail.id)"
    >
        <div class="mb-4 space-y-1">
            <ComponentHeader
                :header-title="$t('academic_calendar.course_work_marksheet')"
                :description="`${classDetail.name} · ${module.code ? `${module.code} — ` : ''}${module.title}`"
            />
            <p class="text-xs text-muted-foreground">{{ academicContextSubtitle }}</p>
        </div>

        <CourseWorkClassMarksheet
            :academic-calendar-class-id="classDetail.id"
            :initial-module-id="module.id"
            :allowed-module-ids="allowedModuleIds"
            :can-create="canCreateCourseWork"
            :can-update="canUpdateCourseWork"
            :can-export="canExportCourseWork"
            :can-import="canImportCourseWork"
            :course-work-export-url="courseWorkExportUrl"
            :course-work-import-url="courseWorkImportUrl"
            :student-course-work-url="studentCourseWorkUrl"
        />
    </PageContainer>
</template>
