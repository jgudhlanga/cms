<script setup lang="ts">
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';
import EditOLevelSubjects from '@/components/students/update/partials/EditOLevelSubjects.vue';
import { Enrolment } from '@/types/enrolments';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';

interface Props {
    requirements?: CourseRequirement | DepartmentLevelRequirement | null;
    application?: Enrolment | null;
    isViewOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;
</script>

<template>
    <div v-if="isEditing">
        <EditOLevelSubjects :results="application?.relationships?.oLevelResults ?? []" :requirements="requirements" />
    </div>
    <div v-else>
        <OLevelMainSubjects :application="application" :is-view-only="isViewOnly" />
        <OLevelOtherSubjects :application="application" :is-view-only="isViewOnly" />
    </div>
</template>
