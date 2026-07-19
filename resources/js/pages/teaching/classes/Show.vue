<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { BaseButton } from '@/components/core/button';
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
                                        <Link
                                            v-if="canEnterMarks"
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
                                            v-if="canImportCourseWork"
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
                                            v-if="canExportCourseWork"
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
        </div>
    </PageContainer>
</template>
