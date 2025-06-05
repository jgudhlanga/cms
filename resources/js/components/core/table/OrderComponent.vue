<script setup lang="ts">
import { IconName, icons } from '@/lib/icons';
</script>

<template>
    <div class="flex justify-center items-center space-x-3">
        <component :is="icons[IconName.arrow_up]" class="text-green-700 size-5 pointer-cursor" />
        <component :is="icons[IconName.arrow_down]" class="text-destructive size-5 pointer-cursor" />
    </div>
</template>
