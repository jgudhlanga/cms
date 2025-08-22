<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

interface Props {
    title: string;
    onEdit: () => void;
    classes?: string;
}

defineProps<Props>();
</script>

<template>
    <BaseButton :title="title" @click="onEdit()" :size="ButtonSize.xs" :variant="ColorVariant.success_outline" classes="rounded-full">
        <BaseIcon :name="IconName.edit" />
    </BaseButton>
</template>
