<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import type { CourseWorkProgressTotals } from '@/types/course-work-progress';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    calendarYear: number;
    currentYear: number;
    availableYears: number[];
    isHistorical: boolean;
    programmes: Array<{
        classConfigId: number;
        programme: string;
        departmentName: string;
        lecturerInCharge: string | null;
        isLecturerInCharge: boolean;
        totals: CourseWorkProgressTotals | null;
    }>;
}>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans('academic_calendar.course_work_progress_title') },
]);

const yearUrl = (year: number): string =>
    year === props.currentYear
        ? route('teaching.course-work-progress.index')
        : route('teaching.course-work-progress.index', { calendar_year: String(year) });

const showUrl = (classConfigId: number): string =>
    route('teaching.course-work-progress.show', { class_config: classConfigId });
</script>

<template>
    <Head :title="$t('academic_calendar.course_work_progress_title')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div>
                <h1 class="text-lg font-semibold">{{ $t('academic_calendar.course_work_progress_title') }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ $t('academic_calendar.course_work_progress_description') }}</p>
            </div>

            <!-- Earlier years are listed only when the viewer has programmes in them. -->
            <nav
                v-if="availableYears.length > 1"
                :aria-label="$tChoice('academic_calendar.calendar_year', 1)"
                class="flex flex-wrap items-center gap-2 text-sm"
            >
                <span class="text-muted-foreground">{{ $tChoice('academic_calendar.calendar_year', 1) }}:</span>
                <template v-for="year in availableYears" :key="year">
                    <span v-if="year === calendarYear" aria-current="page" class="rounded-md bg-muted px-2 py-1 font-semibold">{{ year }}</span>
                    <Link v-else :href="yearUrl(year)" class="rounded-md px-2 py-1 text-primary hover:underline">{{ year }}</Link>
                </template>
            </nav>
            <p v-else class="text-sm">
                <span class="text-muted-foreground">{{ $tChoice('academic_calendar.calendar_year', 1) }}:</span>
                <span class="font-semibold">{{ calendarYear }}</span>
            </p>

            <p
                v-if="isHistorical"
                role="status"
                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
            >
                {{ $t('academic_calendar.course_work_progress_historical_notice', { year: String(calendarYear) }) }}
            </p>

            <Empty v-if="programmes.length === 0" :message="$t('academic_calendar.course_work_progress_empty')" />

            <div v-else class="overflow-x-auto rounded-md border border-border">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">{{ $t('academic_calendar.course_work_progress_title') }} · {{ calendarYear }}</caption>
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-muted-foreground">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $tChoice('trans.course', 1) }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.lecturer_in_charge') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic_calendar.course_work_progress_captured') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in programmes" :key="row.classConfigId" class="border-b border-border/60 last:border-0">
                            <th scope="row" class="px-3 py-2.5 font-medium">
                                <Link :href="showUrl(row.classConfigId)" class="text-primary hover:underline">
                                    {{ row.programme }}
                                </Link>
                                <span class="block text-xs font-normal text-muted-foreground">{{ row.departmentName }}</span>
                            </th>
                            <td class="px-3 py-2.5">{{ row.lecturerInCharge ?? $t('academic_calendar.lecturer_in_charge_not_assigned') }}</td>
                            <td class="px-3 py-2.5">
                                <template v-if="row.totals">
                                    {{ row.totals.captured }} / {{ row.totals.expected }}
                                    <span v-if="row.totals.percent !== null && row.totals.percent !== undefined" class="block text-xs text-muted-foreground">
                                        {{ $t('academic_calendar.course_work_progress_complete_percent', { percent: String(row.totals.percent) }) }}
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PageContainer>
</template>
