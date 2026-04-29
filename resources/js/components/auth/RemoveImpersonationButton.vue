<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

const { navigateTo } = useUtils();
</script>

<template>
    <BaseButton
        :title="$t('trans.ui_remove_impersonation')"
        :size="ButtonSize.xs"
        classes="rounded-full normal-case"
        :variant="ColorVariant.fuchsia"
        @click="navigateTo(route('impersonate.leave'))"
    />
</template>
