<script setup lang="ts">
import HeadingSmall from '../util/HeadingSmall.vue';

interface Props {
    title?: string;
    description?: string;
    colorVariant?: string;
}

withDefaults(defineProps<Props>(), {
    colorVariant: 'primary',
});
</script>

<template>
    <div :class="`flex w-full flex-col space-y-3 rounded-md border-l p-4 shadow-sm border-${colorVariant}`">
        <HeadingSmall v-if="title" :title="title" :description="description" />
        <slot />
    </div>
</template>
