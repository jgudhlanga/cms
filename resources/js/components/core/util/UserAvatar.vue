<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import TextLink from '@/components/core/util/TextLink.vue';

interface Props {
    href: string;
    title?: string;
    src?: string;
    classes?: string;
}

defineProps<Props>();
</script>

<template>
    <TextLink :href="href" :title="title">
        <div class="flex flex-col">
            <BaseImage :is-person="true" :classes="classes" :src="src" />
        </div>
    </TextLink>
</template>
