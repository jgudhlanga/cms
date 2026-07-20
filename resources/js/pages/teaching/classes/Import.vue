<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import CourseWorkImportPanel from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkImportPanel.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { CourseWorkImportResult } from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkImportPanel.vue';
import { Head } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface ClassDetail {
    id: number;
    name: string;
    classConfigId: number;
    modules: Array<{
        id: number;
        courseWorkLock: {
            hasEditableCourseWork: boolean;
            allAssessmentTypesLocked: boolean;
            lockedAssessmentTypeIds: number[];
            lockedAssessmentTypeNames: string[];
            readOnlyMessage: string | null;
        };
    }>;
}

interface Props {
    classDetail: ClassDetail;
    module: { id: number; title: string; code: string };
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canImportCourseWork: boolean;
    courseWorkImportResult?: CourseWorkImportResult | null;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.class', 2), href: route('teaching.classes.index') },
    { title: props.classDetail.name, href: route('teaching.classes.show', props.classDetail.id) },
    { title: trans('academic_calendar.course_work_import') },
]);

const courseWorkImportTemplateUrl = (moduleId: number): string =>
    route('teaching.classes.import.template', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: moduleId,
    });

const courseWorkImportPreviewUrl = computed(() =>
    route('teaching.classes.import.preview', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: props.module.id,
    }),
);

const courseWorkImportProcessUrl = computed(() =>
    route('teaching.classes.import.process', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: props.module.id,
    }),
);

const selectedModuleLock = computed(
    () => props.classDetail.modules.find((module) => module.id === props.module.id)?.courseWorkLock ?? null,
);
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_import_title')" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="
            route('teaching.classes.marksheet', {
                academic_calendar_class: classDetail.id,
                course_syllabus_module: module.id,
            })
        "
    >
        <div class="mb-4 space-y-1">
            <ComponentHeader
                :header-title="$t('academic_calendar.course_work_import_title')"
                :description="`${classDetail.name} · ${module.code ? `${module.code} — ` : ''}${module.title}`"
            />
            <p class="text-xs text-muted-foreground">{{ academicContextSubtitle }}</p>
        </div>

        <CourseWorkImportPanel
            :academic-calendar-class-id="classDetail.id"
            :can-import-course-work="canImportCourseWork"
            :initial-module-id="module.id"
            :allowed-module-ids="[module.id]"
            :course-work-import-template-url="courseWorkImportTemplateUrl"
            :course-work-import-preview-url="courseWorkImportPreviewUrl"
            :course-work-import-process-url="courseWorkImportProcessUrl"
            :course-work-import-result="courseWorkImportResult"
            :read-only="selectedModuleLock?.allAssessmentTypesLocked ?? false"
            :read-only-message="selectedModuleLock?.readOnlyMessage ?? null"
        />
    </PageContainer>
</template>
