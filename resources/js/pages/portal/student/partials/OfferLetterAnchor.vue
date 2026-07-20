<script setup lang="ts">
import { IconName } from '@/enums/icons';

interface Props {
    studentApplicationId: string;
}

defineProps<Props>();
</script>

<template>
    <a
        :href="route('documents.offer-letter', { student_application: studentApplicationId })"
        class="flex h-6 items-center rounded-full border border-primary bg-transparent px-2 py-1 text-xs font-semibold text-primary hover:border-primary hover:bg-primary/20"
        target="_blank"
        rel="noopener noreferrer"
        download
        @click.stop
    >
        <BaseIcon :name="IconName.paperclip" class="size-4 text-primary" />
        <span class="ml-2 font-extrabold uppercase">{{ $t('trans.ui_offer_letter') }}</span>
    </a>
</template>
