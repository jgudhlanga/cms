<script setup lang="ts">
import AppLogoMark from '@/components/core/image/AppLogoMark.vue';
</script>

<template>
    <InfiniteBackground />
    <div class="flex flex-col items-center justify-center space-y-4 overflow-hidden py-8">
        <AppLogoMark size="md" class="my-5" />
        <slot />
    </div>
</template>
