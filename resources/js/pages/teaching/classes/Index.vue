<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import DashboardCard from '@/pages/dashboard/components/DashboardCard.vue';
import TeachingClassPreviewCard from '@/pages/teaching/classes/partials/TeachingClassPreviewCard.vue';
import TeachingClassesSummaryCard from '@/pages/teaching/classes/partials/TeachingClassesSummaryCard.vue';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { TeachingClassCard, TeachingClassesSummary } from '@/types/lecturer';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    classes: TeachingClassCard[];
    summary: TeachingClassesSummary;
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
    canEnterMarks?: boolean;
    canExportCourseWork?: boolean;
    canImportCourseWork?: boolean;
}

defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('dashboard') },
    { title: trans_choice('trans.class', 2) },
]);

const classShowUrl = (classCard: TeachingClassCard): string | null => {
    if (classCard.academicCalendarClassId == null) {
        return null;
    }

    return route('teaching.classes.show', classCard.academicCalendarClassId);
};
</script>

<template>
    <Head :title="$tChoice('trans.class', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <ComponentHeader
                :header-title="$tChoice('trans.class', 2)"
                :description="academicContextSubtitle"
            />

            <TeachingClassesSummaryCard v-if="classes.length > 0" :summary="summary" />

            <DashboardCard :title="$t('dashboard.lecturer_classes')">
                <Empty
                    v-if="classes.length === 0"
                    :message="$t('dashboard.lecturer_no_classes')"
                />
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <TeachingClassPreviewCard
                        v-for="classCard in classes"
                        :key="classCard.academicCalendarClassId ?? classCard.name"
                        :class-card="classCard"
                        :show-url="classShowUrl(classCard)"
                    />
                </div>
            </DashboardCard>
        </div>
    </PageContainer>
</template>
