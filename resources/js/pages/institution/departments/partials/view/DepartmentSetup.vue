<script setup lang="ts">
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
</script>

<template>
    <div class="flex flex-col gap-4">
        <Levels :department="department" />
        <Courses :department="department" />
    </div>
</template>
