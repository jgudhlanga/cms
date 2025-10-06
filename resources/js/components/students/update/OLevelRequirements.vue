<script setup lang="ts">
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';
import { Enrolment } from '@/types/enrolments';

interface Props {
    application?: Enrolment | null;
    isViewOnly?: boolean;
}

defineProps<Props>();
</script>

<template>
    <OLevelMainSubjects :application="application" :is-view-only="isViewOnly" />
    <OLevelOtherSubjects :application="application" :is-view-only="isViewOnly" />
</template>
