<script setup lang="ts">
defineProps<{ deficit: string | number }>();
</script>

<template>
    <div class="flex space-x-3 rounded-full bg-orange-100 px-3 py-1 text-sm font-medium text-orange-500">
        <div>{{ $t('trans.ui_deficit') }}</div>
        <div class="font-bold">{{ deficit }}</div>
    </div>
</template>
