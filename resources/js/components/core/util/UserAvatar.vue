<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { cn } from '@/lib/utils';

interface Props {
    href: string;
    title?: string;
    src?: string;
    classes?: string;
}

defineProps<Props>();
</script>

<template>
    <TextLink :href="href" :title="title" class="flex items-center space-x-2">
        <BaseImage :is-person="true" :classes="cn(classes)" :src="src" />
    </TextLink>
</template>
