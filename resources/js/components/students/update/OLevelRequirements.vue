<script setup lang="ts">
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
}

const props = defineProps<Props>();
const { levelRequirements } = props;
</script>

<template>
    <OLevelMainSubjects :level-requirements="levelRequirements" />
    <OLevelOtherSubjects/>
</template>
