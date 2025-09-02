<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from 'vue';
import lottie from 'lottie-web';

const targetDate = new Date('2025-11-31T23:59:59');

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

onUnmounted(() => {
    clearInterval(timerInterval);
});
</script>

<template>
    <div class="flex h-[70vh] flex-col items-center justify-center bg-transparent p-6 " style="height: 70vh">
        <!-- Lottie animation -->
        <div ref="lottieContainer" class="w-40 h-40 sm:w-52 sm:h-52 md:w-64 md:h-64 mb-6"></div>

        <!-- Text content -->
        <div class="flex flex-col w-full space-y-4 text-center">
            <h1 class="text-5xl font-bold uppercase text-destructive">Coming Soon</h1>
            <div class="flex items-center justify-center w-full text-lg sm:text-lg">We're working hard to bring you something amazing. Stay tuned!</div>

            <!-- Countdown Timer -->
            <div class="mt-4 flex justify-center space-x-6 font-mono text-2xl">
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
        </div>
    </div>
</template>
