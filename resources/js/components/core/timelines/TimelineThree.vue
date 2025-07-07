<script setup lang="ts">
import { TimelineStep } from '@/types/utils';

interface Props {
    steps: TimelineStep[];
}

defineProps<Props>();
</script>

<template>
    <div
        class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:h-full before:w-0.5 before:-translate-x-px before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent md:before:ml-[8.75rem] md:before:translate-x-0"
    >
        <!-- Item #1 -->
        <div class="relative">
            <div class="mb-3 items-center md:flex md:space-x-4">
                <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                    <!-- Icon -->
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white shadow md:order-1">
                        <svg class="fill-emerald-500" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                            <path d="M8 0a8 8 0 1 0 8 8 8.009 8.009 0 0 0-8-8Zm0 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
                        </svg>
                    </div>
                    <!-- Date -->
                    <time class="font-caveat text-xl font-medium text-indigo-500 md:w-28">Apr 7, 2024</time>
                </div>
                <!-- Title -->
                <div class="ml-14 text-slate-500"><span class="font-bold text-slate-900">Mark Mikrol</span> opened the request</div>
            </div>
            <!-- Card -->
            <div class="ml-14 rounded border border-slate-200 bg-white p-4 text-slate-500 shadow md:ml-44">
                Various versions have evolved over the years, sometimes by accident, sometimes on purpose injected humour and the like.
            </div>
        </div>
    </div>
</template>
