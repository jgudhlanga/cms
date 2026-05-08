<script setup lang="ts">
import { IconName, icons } from '@/lib/icons';
</script>

<template>
    <div class="flex items-center justify-center space-x-3">
        <component :is="icons[IconName.arrow_up]" class="pointer-cursor size-5 text-green-700" />
        <component :is="icons[IconName.arrow_down]" class="text-destructive pointer-cursor size-5" />
    </div>
</template>
