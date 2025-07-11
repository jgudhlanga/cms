<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';

interface Props {
    lineOne?: string;
    lineTwo?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="bg-endless h-[150px]"></div>
    <div class="z-10 -mt-[80px] flex flex-col items-center justify-center">
        <BaseImage :src="''" :is-person="true" classes="w-[130px] h-[130px] rounded-full border-2 border-primary shadow-lg" />
        <h1 class="mt-2 text-xl font-bold" v-if="lineOne">{{ lineOne }}</h1>
        <p class="text-muted-foreground pb-2" v-if="lineTwo">{{ lineTwo }}</p>
    </div>
</template>
