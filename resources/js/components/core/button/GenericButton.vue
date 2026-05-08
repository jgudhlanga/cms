<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

defineProps<{ title?: string; variant?: ColorVariant; iconVariant?: ColorVariant; icon?: IconName }>();
</script>

<template>
    <BaseButton type="button" :variant="variant">
        <BaseIcon v-if="icon" :name="icon" :color="iconVariant" />
        <span v-if="title">{{ title }}</span>
        <slot />
    </BaseButton>
</template>
