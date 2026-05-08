<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
</script>

<template>
    <BaseButton type="button" :variant="ColorVariant.warning_outline">
        <BaseIcon :name="IconName.archive" :color="ColorVariant.warning" />
        <span>{{ $tChoice('trans.archive', 1) }}</span>
    </BaseButton>
</template>
