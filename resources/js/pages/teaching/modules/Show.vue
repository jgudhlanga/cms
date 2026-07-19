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

interface ModuleClass {
    id: number;
    name: string;
    classConfigId: number;
    institutionDepartmentId: number;
    calendarYear: string;
}

interface ModuleDetail {
    id: number;
    title: string;
    code: string;
    departmentName: string;
    classes: ModuleClass[];
}

interface Props {
    moduleDetail: ModuleDetail;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canEnterMarks: boolean;
    canExportCourseWork: boolean;
    canImportCourseWork: boolean;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.module', 2), href: route('teaching.modules.index') },
    { title: props.moduleDetail.code || props.moduleDetail.title },
]);

const marksheetUrl = (classId: number): string =>
    route('teaching.classes.marksheet', {
        academic_calendar_class: classId,
        course_syllabus_module: props.moduleDetail.id,
    });

const importUrl = (classId: number): string =>
    route('teaching.classes.import', {
        academic_calendar_class: classId,
        course_syllabus_module: props.moduleDetail.id,
    });
</script>

<template>
    <Head :title="moduleDetail.title" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('teaching.modules.index')">
        <div class="space-y-6">
            <ComponentHeader
                :header-title="moduleDetail.title"
                :description="`${moduleDetail.code ? `${moduleDetail.code} · ` : ''}${academicContextSubtitle}`"
            />

            <DashboardCard :title="$tChoice('trans.department', 1)">
                <p class="text-sm text-foreground">{{ moduleDetail.departmentName || '—' }}</p>
            </DashboardCard>

            <DashboardCard :title="$t('dashboard.lecturer_module_classes')">
                <Empty
                    v-if="moduleDetail.classes.length === 0"
                    :message="$t('dashboard.lecturer_no_classes')"
                />
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-muted-foreground">
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.class', 1) }}</th>
                                <th class="py-2 text-right font-medium">{{ $tChoice('trans.action', 2) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in moduleDetail.classes"
                                :key="row.id"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="py-2.5 pr-3 font-medium text-foreground">
                                    <Link
                                        :href="route('teaching.classes.show', row.id)"
                                        class="text-primary hover:underline"
                                    >
                                        {{ row.name }}
                                    </Link>
                                </td>
                                <td class="py-2.5">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Link
                                            v-if="canEnterMarks"
                                            :href="marksheetUrl(row.id)"
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
                                            :href="importUrl(row.id)"
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
                                                    academic_calendar_class: row.id,
                                                    course_syllabus_module: moduleDetail.id,
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
