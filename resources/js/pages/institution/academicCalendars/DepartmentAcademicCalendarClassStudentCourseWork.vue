<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useDepartmentAcademicCalendarClassStudentCourseWorkNavigation } from '@/composables/academicCalendars/useDepartmentAcademicCalendarClassStudentCourseWorkNavigation';
import { formatCourseWorkStudentContext } from '@/lib/course-work';
import AcademicCalendarClassStudentCourseWorkPanel from '@/pages/institution/academicCalendars/partials/courseWork/AcademicCalendarClassStudentCourseWorkPanel.vue';
import type { AcademicCalendar, AcademicCalendarClassPreviewStudent, ClassConfig } from '@/types/academic-calendar';
import type { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import type { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, toRefs } from 'vue';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    classConfig: ClassConfig | null;
    academicCalendarClass: { id: number; name: string };
    student: AcademicCalendarClassPreviewStudent;
    canCreateCourseWork?: boolean;
    canUpdateCourseWork?: boolean;
    canViewCourseWorkAuditTrail?: boolean;
}>();

const { department, academicCalendar, course, level, mode, classConfig, academicCalendarClass, student } = toRefs(props);

const { classShowUrl, breadcrumbs } = useDepartmentAcademicCalendarClassStudentCourseWorkNavigation(
    department,
    academicCalendar,
    course,
    level,
    mode,
    classConfig,
    academicCalendarClass,
    student,
);

const studentContextLine = computed(() =>
    formatCourseWorkStudentContext(
        [
            student.value.studentNumber,
            academicCalendarClass.value.name,
            course.value.attributes.course,
            level.value.attributes.level,
            mode.value.attributes.name,
        ],
        trans('academic_calendar.course_work_student_context_separator'),
        trans('students.not_available'),
    ),
);
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_student_page_title', { student: student.name })" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="classShowUrl">
        <template #backNavigationLeading>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-foreground">{{ student.name }}</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ studentContextLine }}</p>
            </div>
        </template>

        <AcademicCalendarClassStudentCourseWorkPanel
            :academic-calendar-class-id="academicCalendarClass.id"
            :student-enrolment-id="student.studentEnrolmentId"
            :can-create="canCreateCourseWork"
            :can-update="canUpdateCourseWork"
            :can-view-audit-trail="canViewCourseWorkAuditTrail"
        />
    </PageContainer>
</template>
