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
    <div class="w-full min-w-0 max-w-full overflow-x-clip rounded-xl bg-card text-card-foreground">
        <Header :data="headerData" />
        <div class="w-full min-w-0 px-2 py-0.5 pb-3 sm:px-3 md:pb-1">
            <slot />
        </div>
    </div>
</template>
