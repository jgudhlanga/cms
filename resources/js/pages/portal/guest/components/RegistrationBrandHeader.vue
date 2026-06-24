<script setup lang="ts">
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import AppLogoMark from '@/components/core/image/AppLogoMark.vue';
</script>

<template>
    <header class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-x-2">
        <div aria-hidden="true" />

        <div class="flex flex-col items-center justify-self-center">
            <AppLogoMark size="lg" />
        </div>

        <div class="flex items-center justify-self-end">
            <AppearanceCycleToggle />
        </div>
    </header>
</template>
