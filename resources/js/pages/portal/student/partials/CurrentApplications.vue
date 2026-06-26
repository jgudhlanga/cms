<script setup lang="ts">
import ApplicationCard from '@/components/students/applications/ApplicationCard.vue';
import { useStudents } from '@/composables/students/useStudents';
import { buildProgramEditUrl, navigationOptionsFromQuery, parseStudentShowQuery } from '@/lib/studentShowNavigation';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import { Enrolment } from '@/types/enrolments';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    applications: Enrolment[];
    activeIntakePeriodIds?: Array<string | number>;
    canEdit?: boolean;
    compact?: boolean;
    editable?: boolean;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    activeIntakePeriodIds: () => [],
    canEdit: false,
    compact: false,
    editable: false,
    showHeader: true,
});

const applications = computed(() => props.applications);
const { showEditProgramButton } = useStudents();
const page = usePage();
const navigationOptions = computed(() => {
    const searchParams = new URL(page.url, window.location.origin).searchParams;

    return navigationOptionsFromQuery(parseStudentShowQuery(searchParams));
});

const programEditUrl = (applicationId: string | number) =>
    buildProgramEditUrl(applicationId, navigationOptions.value);

const canEditApplication = (application: Enrolment): boolean =>
    props.editable && props.canEdit && showEditProgramButton(application, props.activeIntakePeriodIds);
</script>

<template>
    <div
        v-if="applications && applications.length > 0"
        class="flex w-full flex-col"
    >
        <ComponentHeader
            v-if="showHeader"
            header-title="Current applications"
            :description="$t('trans.ui_overview_of_your_applications')"
            class="mb-3"
        />
        <div class="space-y-3">
            <ApplicationCard
                v-for="application in applications"
                :key="application.id"
                :application="application"
                :compact="compact"
                :can-edit="canEditApplication(application)"
                :edit-url="programEditUrl(application.id)"
            />
        </div>
    </div>
</template>
