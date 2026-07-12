<script setup lang="ts">
import StudentCourseWorkModuleList from '@/components/students/course-work/StudentCourseWorkModuleList.vue';
import { mapDashboardModuleToListItem } from '@/composables/students/studentProgrammeDisplay';
import { useUtils } from '@/composables/core/useUtils';
import type { StudentPortalDashboardModule, StudentPortalDashboardTerm } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    modules?: StudentPortalDashboardModule[];
    currentTerm?: StudentPortalDashboardTerm | null;
}

const props = withDefaults(defineProps<Props>(), {
    modules: () => [],
    currentTerm: null,
});

const { navigateTo } = useUtils();

const listItems = computed(() => props.modules.map(mapDashboardModuleToListItem));

const subtitle = computed(() => {
    const description = trans('students.dashboard_course_work_description');

    if (props.currentTerm) {
        return `${props.currentTerm.label} · ${props.currentTerm.calendarYear} · ${description}`;
    }

    return description;
});

const programsUrl = computed(() => route('portal.profile.programs'));

const openPrograms = (): void => {
    navigateTo(programsUrl.value);
};
</script>

<template>
    <StudentCourseWorkModuleList
        :modules="listItems"
        :title="$t('students.dashboard_course_work')"
        :subtitle="subtitle"
        :view-all-href="programsUrl"
        :view-all-label="$t('students.dashboard_view_all')"
        :empty-message="$t('students.no_modules')"
        mode="navigate"
        @activate="openPrograms"
        @view-all="openPrograms"
    />
</template>
