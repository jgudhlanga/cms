<script setup lang="ts">
import StudentAccommodationPanel from '@/components/students/accommodation/StudentAccommodationPanel.vue';
import type { Student } from '@/types/students';

interface Props {
    student: Student;
    context?: 'admin' | 'portal';
}

withDefaults(defineProps<Props>(), {
    context: 'admin',
});
</script>

<template>
    <StudentAccommodationPanel :student="student" :context="context" />
</template>
