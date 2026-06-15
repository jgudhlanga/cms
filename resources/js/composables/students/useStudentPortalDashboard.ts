import { scoreBarColor } from '@/composables/students/studentProgrammeDisplay';
import { jsonApiRequestConfig, parseJsonApiStudentPortalDashboardStats } from '@/lib/json-api';
import { getTimeOfDayForDate, resolveUserTimeZone, timeOfDayGreetingKey } from '@/lib/timeOfDay';
import HttpService from '@/services/http.service';
import type { StudentPortalDashboardStats } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { computed, inject, type InjectionKey, ref } from 'vue';

const emptyStats = (): StudentPortalDashboardStats => ({
    activeModuleCount: 0,
    totalModuleHours: 0,
    averageCourseWorkScore: null,
    oLevelSubjectCount: 0,
    applicationCount: 0,
    pendingApplicationCount: 0,
    modules: [],
    activities: [],
    notices: [],
    calendarType: 'semester',
    currentTerm: null,
    nextTerm: null,
});

export function useStudentPortalDashboard() {
    const stats = ref<StudentPortalDashboardStats>(emptyStats());
    const isLoading = ref(false);
    const loadError = ref(false);

    const fetchDashboardStats = async (): Promise<void> => {
        try {
            isLoading.value = true;
            loadError.value = false;

            const document = await HttpService.get(
                route('v1.json.student-programs.dashboardStats'),
                jsonApiRequestConfig(),
            );

            stats.value = parseJsonApiStudentPortalDashboardStats(document);
        } catch {
            loadError.value = true;
            stats.value = emptyStats();
        } finally {
            isLoading.value = false;
        }
    };

    const userTimeZone = resolveUserTimeZone();

    const greeting = computed(() => trans(timeOfDayGreetingKey(getTimeOfDayForDate(new Date(), userTimeZone))));

    return {
        stats,
        isLoading,
        loadError,
        fetchDashboardStats,
        greeting,
        userTimeZone,
        scoreBarColor,
    };
}

export type StudentPortalDashboardContext = ReturnType<typeof useStudentPortalDashboard>;

export const studentPortalDashboardKey: InjectionKey<StudentPortalDashboardContext> =
    Symbol('studentPortalDashboard');

export function useInjectedStudentPortalDashboard(): StudentPortalDashboardContext {
    const context = inject(studentPortalDashboardKey);

    if (!context) {
        throw new Error(
            'useInjectedStudentPortalDashboard must be used within a component that provides studentPortalDashboardKey',
        );
    }

    return context;
}
