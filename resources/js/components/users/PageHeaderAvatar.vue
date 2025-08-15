<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import BaseTag from '@/components/core/util/BaseTag.vue';

interface Props {
    lineOne?: string;
    lineTwo?: string;
    showUserProfileLink?: boolean;
}

defineProps<Props>();
</script>

<template>
    <div class="bg-endless h-[150px]"></div>
    <div class="z-10 my-1 -mt-[80px] flex flex-col items-center justify-center">
        <BaseImage :src="''" :is-person="true" classes="w-[130px] h-[130px] rounded-full border-2 border-primary shadow-lg" />
        <h2 class="text-md mt-2 font-bold" v-if="lineOne">{{ lineOne }}</h2>
        <BaseTag v-if="lineTwo" :title="lineTwo" />
    </div>
</template>
