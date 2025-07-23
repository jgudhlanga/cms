<script setup lang="ts">
import { TimelineStep } from '@/types/utils';

interface Props {
    steps: TimelineStep[];
}

defineProps<Props>();
</script>

<template>
    <div
        class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:h-full before:w-0.5 before:-translate-x-px before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent md:before:mx-auto md:before:translate-x-0"
    >
        <!-- Item #1 -->
        <div
            v-for="(step, index) in steps"
            :key="index"
            class="group is-active relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse"
        >
            <!-- Icon -->
            <div
                class="group-[.is-active]:bg-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white bg-slate-300 text-slate-500 shadow group-[.is-active]:text-emerald-50 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2"
            >
                <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="10">
                    <path
                        fill-rule="nonzero"
                        d="M10.422 1.257 4.655 7.025 2.553 4.923A.916.916 0 0 0 1.257 6.22l2.75 2.75a.916.916 0 0 0 1.296 0l6.415-6.416a.916.916 0 0 0-1.296-1.296Z"
                    />
                </svg>
            </div>
            <!-- Card -->
            <div class="w-[calc(100%-4rem)] border border-slate-200 bg-white p-4 shadow md:w-[calc(50%-2.5rem)] rounded-2xl">
                <div class="mb-1 flex items-center justify-between space-x-2">
                    <div class="font-semibold uppercase text-sm">{{ step.title }}</div>
                    <time class="font-caveat text-primary font-medium">{{ step.label }}</time>
                </div>
                <div class="text-muted-foreground">{{ step.description }}</div>
                <component :is="step.component"  v-bind="step.props" />
            </div>
        </div>
    </div>
</template>
