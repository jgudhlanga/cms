<script setup lang="ts">
defineProps<{ classSize: string | number }>();
</script>

<template>
    <div class="flex items-center space-x-3 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-600 uppercase">
        <div>{{ $t('trans.ui_course_size') }}</div>
        <div class="font-bold">{{ classSize }}</div>
    </div>
</template>
