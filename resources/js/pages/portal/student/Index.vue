<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import StudentDashboardActivity from '@/components/portal/dashboard/StudentDashboardActivity.vue';
import StudentDashboardHeader from '@/components/portal/dashboard/StudentDashboardHeader.vue';
import StudentDashboardModuleProgress from '@/components/portal/dashboard/StudentDashboardModuleProgress.vue';
import StudentDashboardNoticeboard from '@/components/portal/dashboard/StudentDashboardNoticeboard.vue';
import StudentDashboardQuickActions from '@/components/portal/dashboard/StudentDashboardQuickActions.vue';
import StudentDashboardTermDetails from '@/components/portal/dashboard/StudentDashboardTermDetails.vue';
import { useStudentPortalDashboard, studentPortalDashboardKey } from '@/composables/students/useStudentPortalDashboard';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { onMounted, provide } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
}

const props = defineProps<Props>();
const { user } = props.auth;

const dashboard = useStudentPortalDashboard();
const { stats, isLoading, loadError, fetchDashboardStats } = dashboard;

provide(studentPortalDashboardKey, dashboard);

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }, { title: user.attributes?.name }];

onMounted(() => {
    void fetchDashboardStats();
});
</script>

<template>
    <Head :title="$tChoice('trans.dashboard', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="mt-2 flex w-full min-w-0 max-w-full flex-col gap-5 overflow-x-clip">
            <StudentDashboardHeader :student="student" />

            <div
                v-if="loadError"
                class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-700 dark:text-red-400"
            >
                <p>{{ $t('students.dashboard_load_failure') }}</p>
                <button
                    type="button"
                    class="mt-2 text-xs font-semibold uppercase tracking-wide underline"
                    @click="fetchDashboardStats()"
                >
                    {{ $t('students.reset') }}
                </button>
            </div>

            <DataLoadingSpinner v-if="isLoading && !loadError" />

            <template v-else-if="!loadError">
                <StudentDashboardTermDetails
                    :calendar-type="stats.calendarType"
                    :current-term="stats.currentTerm"
                    :next-term="stats.nextTerm"
                />

                <StudentDashboardQuickActions />

                <StudentDashboardModuleProgress
                    :modules="stats.modules"
                    :current-term="stats.currentTerm"
                />

                <div class="grid gap-5 lg:grid-cols-2">
                    <StudentDashboardNoticeboard :notices="stats.notices" />
                    <StudentDashboardActivity :activities="stats.activities" />
                </div>
            </template>
        </div>
    </PageContainer>
</template>
