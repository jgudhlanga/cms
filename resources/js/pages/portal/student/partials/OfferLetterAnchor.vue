<script setup lang="ts">
import { IconName } from '@/enums/icons';

interface Props {
    studentProgramId: string;
}

defineProps<Props>();
</script>

<template>
    <a
        :href="route('documents.offer-letter', { student_program: studentProgramId })"
        class="border-persian-600 text-persian-600 hover:bg-persian-200 hover:border-persian-200 flex h-6 items-center rounded-full bg-transparent px-2 py-1 text-xs"
        target="_blank"
    >
        <BaseIcon :name="IconName.paperclip" class="size-4" />
        <span class="ml-2 font-extrabold uppercase">Download Offer Letter</span>
    </a>
</template>
