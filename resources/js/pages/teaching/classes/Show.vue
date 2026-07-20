<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import Empty from '@/components/core/util/Empty.vue';
import { BaseButton } from '@/components/core/button';
import { normalizeGender } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import DashboardCard from '@/pages/dashboard/components/DashboardCard.vue';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { Head, Link } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface ClassModule {
    id: number;
    title: string;
    code: string;
    canManage: boolean;
    captureMarkOnly?: boolean;
    courseWorkLock: {
        hasEditableCourseWork: boolean;
        allAssessmentTypesLocked: boolean;
        lockedAssessmentTypeIds: number[];
        lockedAssessmentTypeNames: string[];
        readOnlyMessage: string | null;
    };
}

interface ClassStudent {
    studentEnrolmentId: number;
    studentId: number;
    applicationTrackingNumber: string | null;
    studentNumber?: string | null;
    gender?: string | null;
    name: string;
}

interface ClassDetail {
    id: number;
    name: string;
    description: string | null;
    departmentName: string;
    courseName: string;
    levelName: string;
    modeOfStudyName: string;
    calendarYear: string;
    classConfigId: number;
    institutionDepartmentId: number;
    isTutor: boolean;
    studentCount: number;
    students: ClassStudent[];
    modules: ClassModule[];
}

interface Props {
    classDetail: ClassDetail;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canEnterMarks: boolean;
    canCreateCourseWork: boolean;
    canUpdateCourseWork: boolean;
    canExportCourseWork: boolean;
    canImportCourseWork: boolean;
    canExportClassList: boolean;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.class', 2), href: route('teaching.classes.index') },
    { title: props.classDetail.name },
]);

const marksheetUrl = (moduleId: number): string =>
    route('teaching.classes.marksheet', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: moduleId,
    });

const importUrl = (moduleId: number): string =>
    route('teaching.classes.import', {
        academic_calendar_class: props.classDetail.id,
        course_syllabus_module: moduleId,
    });

const classListExportUrl = computed(() =>
    route('teaching.classes.class-list.export', props.classDetail.id),
);

const sortedStudents = computed(() =>
    [...props.classDetail.students].sort((left, right) => {
        const genderPriority: Record<'female' | 'male' | 'unknown', number> = {
            female: 0,
            male: 1,
            unknown: 2,
        };

        const genderSort =
            genderPriority[normalizeGender(left.gender)] - genderPriority[normalizeGender(right.gender)];

        if (genderSort !== 0) {
            return genderSort;
        }

        return left.name.localeCompare(right.name);
    }),
);
</script>

<template>
    <Head :title="classDetail.name" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('teaching.classes.index')">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 border-b border-border pb-4 sm:flex-row sm:items-start sm:justify-between">
                <ComponentHeader
                    :header-title="classDetail.name"
                    :description="academicContextSubtitle"
                />
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="canExportClassList"
                        :href="classListExportUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex"
                    >
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                        >
                            {{ $t('dashboard.lecturer_export_class_list') }}
                        </BaseButton>
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <DashboardCard :title="$tChoice('trans.department', 1)">
                    <p class="text-sm text-foreground">{{ classDetail.departmentName || '—' }}</p>
                </DashboardCard>
                <DashboardCard :title="$tChoice('trans.course', 1)">
                    <p class="text-sm text-foreground">{{ classDetail.courseName || '—' }}</p>
                </DashboardCard>
                <DashboardCard :title="$tChoice('trans.level', 1)">
                    <p class="text-sm text-foreground">{{ classDetail.levelName || '—' }}</p>
                </DashboardCard>
                <DashboardCard :title="$tChoice('trans.student', 2)">
                    <p class="text-sm font-medium text-foreground">{{ classDetail.studentCount }}</p>
                    <p v-if="classDetail.isTutor" class="mt-1 text-xs text-muted-foreground">
                        {{ $t('dashboard.lecturer_is_tutor') }}
                    </p>
                </DashboardCard>
            </div>

            <DashboardCard :title="$t('dashboard.lecturer_class_modules')">
                <Empty
                    v-if="classDetail.modules.length === 0"
                    :message="$t('dashboard.lecturer_no_modules')"
                />
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-muted-foreground">
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.code', 1) }}</th>
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                                <th class="py-2 text-right font-medium">{{ $tChoice('trans.action', 2) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="module in classDetail.modules"
                                :key="module.id"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="py-2.5 pr-3 font-mono text-xs text-muted-foreground">
                                    {{ module.code || '—' }}
                                </td>
                                <td class="py-2.5 pr-3 font-medium text-foreground">{{ module.title }}</td>
                                <td class="py-2.5">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <p
                                            v-if="module.courseWorkLock.readOnlyMessage"
                                            class="mb-2 text-right text-xs text-muted-foreground"
                                        >
                                            {{ module.courseWorkLock.readOnlyMessage }}
                                        </p>
                                        <Link
                                            v-if="canEnterMarks && module.canManage && module.courseWorkLock.hasEditableCourseWork"
                                            :href="marksheetUrl(module.id)"
                                            class="inline-flex"
                                        >
                                            <BaseButton
                                                type="button"
                                                :variant="ColorVariant.primary"
                                                :size="ButtonSize.xs"
                                                classes="rounded-full"
                                            >
                                                {{ $t('dashboard.lecturer_action_enter_marks') }}
                                            </BaseButton>
                                        </Link>
                                        <Link
                                            v-if="canImportCourseWork && module.canManage && module.courseWorkLock.hasEditableCourseWork"
                                            :href="importUrl(module.id)"
                                            class="inline-flex"
                                        >
                                            <BaseButton
                                                type="button"
                                                :variant="ColorVariant.primary_outline"
                                                :size="ButtonSize.xs"
                                                classes="rounded-full"
                                            >
                                                {{ $t('academic_calendar.course_work_import') }}
                                            </BaseButton>
                                        </Link>
                                        <a
                                            v-if="canExportCourseWork && module.canManage"
                                            :href="
                                                route('teaching.classes.marksheet.export', {
                                                    academic_calendar_class: classDetail.id,
                                                    course_syllabus_module: module.id,
                                                    format: 'xlsx',
                                                })
                                            "
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex"
                                        >
                                            <BaseButton
                                                type="button"
                                                :variant="ColorVariant.primary_outline"
                                                :size="ButtonSize.xs"
                                                classes="rounded-full"
                                            >
                                                {{ $t('academic_calendar.course_work_export_excel') }}
                                            </BaseButton>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardCard>

            <BaseAccordion>
                <BaseAccordionItem
                    value="class-students"
                    :title="$tChoice('trans.student', 2)"
                    :description="$t('dashboard.lecturer_class_students_accordion_description', {
                        count: String(classDetail.studentCount),
                    })"
                >
                    <Empty
                        v-if="sortedStudents.length === 0"
                        :message="$t('academic_calendar.course_work_no_students')"
                    />
                    <div v-else class="overflow-x-auto">
                        <table class="j-table min-w-full">
                            <thead class="j-thead">
                                <tr class="j-th">
                                    <th class="j-th text-left">#</th>
                                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                                    <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                                    <th class="j-th text-left">{{ $tChoice('trans.gender', 1) }}</th>
                                    <th class="j-th text-right">{{ $tChoice('trans.action', 2) }}</th>
                                </tr>
                            </thead>
                            <tbody class="j-tbody">
                                <tr
                                    v-for="(student, index) in sortedStudents"
                                    :key="student.studentEnrolmentId"
                                    class="j-tr"
                                >
                                    <td class="j-td">{{ index + 1 }}</td>
                                    <td class="j-td font-medium text-foreground">{{ student.name }}</td>
                                    <td class="j-td font-mono text-xs">
                                        {{ student.studentNumber ?? student.applicationTrackingNumber ?? '—' }}
                                    </td>
                                    <td class="j-td text-sm text-muted-foreground">{{ student.gender || '—' }}</td>
                                    <td class="j-td text-right">
                                        <Link
                                            :href="
                                                route('teaching.classes.student-course-work', {
                                                    academic_calendar_class: classDetail.id,
                                                    student_enrolment: student.studentEnrolmentId,
                                                })
                                            "
                                            class="inline-flex"
                                        >
                                            <BaseButton
                                                type="button"
                                                :variant="ColorVariant.primary_outline"
                                                :size="ButtonSize.xs"
                                                classes="rounded-full"
                                            >
                                                {{ $tChoice('academic_calendar.course_work', 1) }}
                                            </BaseButton>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseAccordionItem>
            </BaseAccordion>
        </div>
    </PageContainer>
</template>
