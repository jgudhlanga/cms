<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';

interface Props {
    actionable: boolean;
    title: string;
    routeName?: string;
    variant?: ColorVariant;
}
defineProps<Props>();
</script>

<template>
    <TextLink
        v-if="actionable"
        :title="title"
        :href="routeName ?? ''"
        classes="inline-flex min-w-8 items-center justify-center rounded-full border border-primary/30 bg-primary/15 px-2 py-1 text-primary hover:bg-primary hover:text-primary-foreground"
    />
    <span v-else>{{ title }}</span>
</template>
