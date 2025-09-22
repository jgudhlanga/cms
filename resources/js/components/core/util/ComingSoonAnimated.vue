<script lang="ts" setup>
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/lib/icons';
import lottie from 'lottie-web';
import { onMounted, onUnmounted, ref } from 'vue';

const targetDate = new Date('2025-09-31T23:59:59');

const days = ref(0);
const hours = ref(0);

const minutes = ref(0);
const seconds = ref(0);

const updateTimer = () => {
    const now = new Date();
    const timeLeft = targetDate.getTime() - now.getTime();

    if (timeLeft > 0) {
        days.value = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        hours.value = Math.floor((timeLeft / (1000 * 60 * 60)) % 24);
        minutes.value = Math.floor((timeLeft / (1000 * 60)) % 60);
        seconds.value = Math.floor((timeLeft / 1000) % 60);
    } else {
        days.value = hours.value = minutes.value = seconds.value = 0;
    }
};

let timerInterval: any;
const lottieContainer = ref(null);

onMounted(() => {
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);

    lottie.loadAnimation({
        container: lottieContainer.value,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: '/assets/json/web_construction.json',
    });
});

const showCountdown = false;

onUnmounted(() => {
    clearInterval(timerInterval);
});
const { goBack } = useUtils();
</script>

<template>
    <div class="flex h-[70vh] flex-col items-center justify-center bg-transparent p-6" style="height: 70vh">
        <!-- Lottie animation -->
        <div ref="lottieContainer" class="mb-6 h-40 w-40 sm:h-52 sm:w-52 md:h-64 md:w-64"></div>

        <!-- Text content -->
        <div class="flex w-full flex-col space-y-4 text-center">
            <h1 class="text-destructive text-5xl font-bold uppercase">Coming Soon</h1>
            <div class="flex w-full items-center justify-center text-lg sm:text-lg">We're still configuring the system. Stay tuned!</div>

            <!-- Countdown Timer -->
            <div v-if="showCountdown" class="mt-4 flex justify-center space-x-6 font-mono text-2xl">
                <div>
                    <span class="block text-5xl font-bold">{{ days }}</span>
                    <span class="text-sm uppercase">Days</span>
                </div>
                <div>
                    <span class="block text-5xl font-bold">{{ hours }}</span>
                    <span class="text-sm uppercase">Hours</span>
                </div>
                <div>
                    <span class="block text-5xl font-bold">{{ minutes }}</span>
                    <span class="text-sm uppercase">Minutes</span>
                </div>
                <div>
                    <span class="block text-5xl font-bold">{{ seconds }}</span>
                    <span class="text-sm uppercase">Seconds</span>
                </div>
            </div>
            <div class="mt-4 flex justify-center space-x-6 font-mono text-2xl">
                <BaseButton @click="goBack()">
                    <BaseIcon :name="IconName.chevron_double_left" class="mr-2 size-5" />
                    <span>Go Back</span>
                </BaseButton>
            </div>
        </div>
    </div>
</template>
