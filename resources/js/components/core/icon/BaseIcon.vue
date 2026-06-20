<script setup lang="ts">
import { IconName } from '@/enums/icons';

import { ColorVariant } from '@/enums/colors';
import { icons } from '@/lib/icons';

withDefaults(defineProps<{ name: IconName; color?: ColorVariant; size?: string }>(), {
    color: ColorVariant.shade,
});
</script>

<template>
    <component :is="icons[name]" :class="$attrs.class" v-bind="$attrs" :size="size" />
</template>
