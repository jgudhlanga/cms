<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { useUtils } from '@/composables/core/useUtils';

const {navigateTo} = useUtils()
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
