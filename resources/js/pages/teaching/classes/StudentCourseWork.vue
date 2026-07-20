<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import AcademicCalendarClassStudentCourseWorkPanel from '@/pages/institution/academicCalendars/partials/courseWork/AcademicCalendarClassStudentCourseWorkPanel.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface ClassDetail {
    id: number;
    name: string;
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
    student: {
        studentEnrolmentId: number;
        studentId: number;
        studentName: string;
        studentNumber: string | null;
    };
    allowedModuleIds: number[];
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canCreateCourseWork: boolean;
    canUpdateCourseWork: boolean;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.class', 2), href: route('teaching.classes.index') },
    { title: props.classDetail.name, href: route('teaching.classes.show', props.classDetail.id) },
    { title: props.student.studentName || trans_choice('trans.student', 1) },
]);

const moduleLocks = computed<Record<number, ClassDetail['modules'][number]['courseWorkLock']>>(() =>
    Object.fromEntries(
        props.classDetail.modules.map((module) => [module.id, module.courseWorkLock]),
    ),
);
</script>

<template>
    <Head :title="student.studentName || $tChoice('trans.student', 1)" />
    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('teaching.classes.show', classDetail.id)"
    >
        <div class="mb-4 space-y-1">
            <ComponentHeader
                :header-title="student.studentName || $tChoice('trans.student', 1)"
                :description="`${classDetail.name}${student.studentNumber ? ` · ${student.studentNumber}` : ''}`"
            />
            <p class="text-xs text-muted-foreground">{{ academicContextSubtitle }}</p>
        </div>

        <AcademicCalendarClassStudentCourseWorkPanel
            :academic-calendar-class-id="classDetail.id"
            :student-enrolment-id="student.studentEnrolmentId"
            :can-create="canCreateCourseWork"
            :can-update="canUpdateCourseWork"
            :can-view-audit-trail="false"
            :module-locks="moduleLocks"
        />
    </PageContainer>
</template>
