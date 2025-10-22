<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

const { navigateTo } = useUtils();
</script>

<template>
    <BaseButton
        title="Remove Impersonation"
        :size="ButtonSize.xs"
        classes="rounded-full normal-case"
        :variant="ColorVariant.fuchsia_outline"
        @click="navigateTo(route('impersonate.leave'))"
    />
</template>
