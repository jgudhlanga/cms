<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Student } from '@/types/students';
interface Props {
    student: Student;
}

const props = defineProps<Props>();
const { navigateTo } = useUtils();
</script>

<template>
    <GenericButton
        :icon="IconName.add"
        class="w-full rounded-full md:w-50"
        :icon-variant="ColorVariant.white"
        :variant="ColorVariant.primary_outline"
        @click="() => navigateTo(route('portal.add-program', { student: props.student.id }))"
        title="Add New Program"
    />
</template>

