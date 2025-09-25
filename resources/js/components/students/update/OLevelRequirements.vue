<script setup lang="ts">
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';
import { Enrolment } from '@/types/enrolments';

interface Props {
    application?: Enrolment | null;
}

defineProps<Props>();
</script>

<template>
    <OLevelMainSubjects :application="application" />
    <OLevelOtherSubjects :application="application" />
</template>
