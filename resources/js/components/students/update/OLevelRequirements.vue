<script setup lang="ts">
import OLevelMainSubjects from '@/components/students/update/OLevelMainSubjects.vue';
import OLevelOtherSubjects from '@/components/students/update/OLevelOtherSubjects.vue';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
    form: InertiaForm<CreateApplicationParams>;
}

const props = defineProps<Props>();
const { levelRequirements } = props;
</script>

<template>
    <OLevelMainSubjects :level-requirements="levelRequirements" />
    <OLevelOtherSubjects :level-requirements="levelRequirements" />
</template>
