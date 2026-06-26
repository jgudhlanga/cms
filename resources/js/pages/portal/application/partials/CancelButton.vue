<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';

const { navigateTo } = useUtils();
</script>

<template>
    <button
        type="button"
        class="inline-flex w-full cursor-pointer items-center justify-center rounded-[10px] border border-border bg-card px-4 py-2.5 text-base font-semibold text-foreground transition-colors hover:bg-muted/50"
        @click="() => navigateTo(route('portal.application.level-options'))"
    >
        {{ $t('trans.portal_change_level') }}
    </button>
</template>
