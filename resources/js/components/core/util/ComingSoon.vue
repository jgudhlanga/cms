<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const showCountdown = false;

const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
});

let intervalId: any;
const targetDate = new Date('2026-01-06T08:59:59');

const updateCountdown = () => {
    const now = new Date();
    const diff = targetDate.getTime() - now.getTime(); // ✅ Fix here

    if (diff <= 0) {
        countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' };
        clearInterval(intervalId);
        return;
    }

    const seconds = Math.floor((diff / 1000) % 60);
    const minutes = Math.floor((diff / 1000 / 60) % 60);
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    countdown.value = {
        days: String(days).padStart(2, '0'),
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
    };
};

onMounted(() => {
    updateCountdown();
    intervalId = setInterval(updateCountdown, 1000);
});

onBeforeUnmount(() => {
    clearInterval(intervalId);
});
</script>
<template>
    <div class="bg-muted flex h-[70vh] flex-col items-center justify-center px-4 text-center">
        <div class="w-full max-w-xl">
            <h1 class="mb-4 text-5xl font-bold text-red-500">{{ $t('trans.ui_coming_soon') }}</h1>
            <p class="mb-6 text-lg text-gray-600">{{ $t('trans.ui_we_re_working_on_something_great_this_page_will_be_live_soon') }}</p>

            <!-- Optional Countdown -->
            <div v-if="showCountdown" class="flex justify-center gap-6 font-mono text-xl text-gray-700">
                <div>
                    <div class="text-3xl font-bold">{{ countdown.days }}</div>
                    <div class="text-sm uppercase">{{ $t('trans.ui_days') }}</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">{{ countdown.hours }}</div>
                    <div class="text-sm uppercase">{{ $t('trans.ui_hours') }}</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">{{ countdown.minutes }}</div>
                    <div class="text-sm uppercase">{{ $t('trans.ui_minutes') }}</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">{{ countdown.seconds }}</div>
                    <div class="text-sm uppercase">{{ $t('trans.ui_seconds') }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
