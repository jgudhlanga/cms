<script setup lang="ts">
import BaseImage from '../image/BaseImage.vue';

defineProps<{ message?: string }>();
</script>
<template>
    <div class="flex h-full w-full flex-col items-center justify-center p-3">
        <BaseImage src="/assets/images/sad.svg" classes="w-[50px] h-[50px]" />
        <div class="font-extralight text-slate-400 dark:text-slate-200">{{ message ? message : $t('trans.no_data') }}</div>
    </div>
</template>
