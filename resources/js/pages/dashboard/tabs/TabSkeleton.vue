<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
</script>

<template>
    <div class="mt-4 space-y-3" aria-busy="true">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Skeleton v-for="card in 4" :key="card" class="h-24 w-full rounded-lg" />
        </div>
        <Skeleton class="h-64 w-full rounded-lg" />
    </div>
</template>
