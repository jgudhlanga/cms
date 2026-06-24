<script setup lang="ts">
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import AppLogo from '@/components/core/image/AppLogo.vue';
</script>

<template>
    <header class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-x-2">
        <div aria-hidden="true" />

        <div class="flex flex-col items-center justify-self-center">
            <AppLogo classes="size-14 object-contain" />
        </div>

        <div class="flex items-center justify-self-end">
            <AppearanceCycleToggle />
        </div>
    </header>
</template>
