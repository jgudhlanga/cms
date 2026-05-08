<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
</script>

<template>
    <BaseButton type="button" :variant="ColorVariant.fuchsia_outline">
        <BaseIcon :name="IconName.back" :color="ColorVariant.fuchsia" />
        <span>{{ $t('trans.back') }}</span>
    </BaseButton>
</template>
