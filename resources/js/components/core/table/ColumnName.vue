<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';

defineProps<{ title: any; iconName: IconName; color: ColorVariant }>();
</script>

<template>
    <div class="flex items-center space-x-2">
        <component :is="icons[iconName]" v-if="iconName" class="h-4 w-4" />
        <span>{{ title }}</span>
    </div>
</template>
