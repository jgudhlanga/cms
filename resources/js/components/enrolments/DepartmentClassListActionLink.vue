<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';

interface Props {
    actionable: boolean;
    title: string;
    routeName: string;
    variant?: ColorVariant;
}
defineProps<Props>();
</script>

<template>
    <TextLink
        v-if="actionable"
        :title="title"
        :href="routeName"
        classes="size-4  bg-persian-100 rounded-full px-2 py-1 hover:bg-persian-600 hover:text-persian-100"
    />
    <span v-else>{{ title }}</span>
</template>
