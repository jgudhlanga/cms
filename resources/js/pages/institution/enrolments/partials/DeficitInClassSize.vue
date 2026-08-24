<script setup lang="ts">
defineProps<{ deficit: string | number }>();
</script>

<template>
    <div class="inline-flex items-center gap-1.5 rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-medium leading-5 text-orange-600 uppercase">
        <span>{{ $t('trans.ui_deficit') }}</span>
        <span class="font-bold tabular-nums">{{ deficit }}</span>
    </div>
</template>
