<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';

const { navigateTo } = useUtils();
</script>

<template>
    <button
        @click="() => navigateTo(route('portal.application.level-options'))"
        class="inline-flex w-full cursor-pointer items-center justify-center rounded-[10px] border-0 bg-linear-to-br from-red-600 to-pink-400 p-2.5 text-lg font-semibold text-white uppercase shadow-[0_4px_15px_rgba(0,0,0,0.1)] transition-all duration-300"
    >
        {{ $t('trans.cancel') }}
    </button>
</template>
