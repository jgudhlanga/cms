<script setup lang="ts">

import AppLogo from '@/components/core/image/AppLogo.vue';
import InfiniteBackground from '@/pages/applications/InfiniteBackground.vue';
</script>

<template>
    <InfiniteBackground />
    <div class="flex flex-col items-center justify-center space-y-4 overflow-scroll py-8">
        <div class="my-5 flex size-20 flex-col items-center justify-center">
            <AppLogo classes="border-2 border-white" />
        </div>
        <slot/>
    </div>
</template>
