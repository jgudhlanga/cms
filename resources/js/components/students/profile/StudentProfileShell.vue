<script setup lang="ts">
import Header from '@/components/students/profile/Header.vue';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import type { Student } from '@/types/students';
import type { StudentProfileTabValue } from '@/composables/students/useStudentProfile';

interface Props {
    student: Student;
    activeTab: StudentProfileTabValue;
}

const props = defineProps<Props>();
const { headerData } = useStudentProfileHeader(() => props.student);
</script>

<template>
    <div class="overflow-hidden rounded-xl bg-card text-card-foreground">
        <Header :data="headerData" />
        <div class="px-2 py-1">
            <slot />
        </div>
    </div>
</template>
