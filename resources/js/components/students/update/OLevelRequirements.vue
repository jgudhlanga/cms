<script setup lang="ts">
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';
import EditOLevelSubjects from '@/components/students/update/partials/EditOLevelSubjects.vue';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { Enrolment } from '@/types/enrolments';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    requirements?: CourseRequirement | DepartmentLevelRequirement | null;
    application?: Enrolment | null;
    isViewOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});
const { application } = props;

const page = usePage();

const isApplicationEditPage = computed(() => page.url.startsWith('/portal/application/') && page.url.endsWith('/edit'));
</script>

<template>
    <div v-if="isApplicationEditPage">
        <EditOLevelSubjects
            :results="application?.relationships?.oLevelResults ?? []"
            :requirements="requirements"
            :student-id="String(application?.attributes?.studentId)"
        />
    </div>
    <div v-else>
        <OLevelMainSubjects :application="application" :is-view-only="isViewOnly" />
        <OLevelOtherSubjects :application="application" :is-view-only="isViewOnly" />
    </div>
</template>
