<script setup lang="ts">
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

const props = defineProps<{
    url: string;
}>();
</script>

<template>
    <BaseButton :title="$tChoice('trans.back', 1)" :size="ButtonSize.sm" :variant="ColorVariant.shade" @click="() => $inertia.visit(url)">
        <BaseIcon :name="IconName.back" :color="ColorVariant.shade" />
    </BaseButton>
</template>
