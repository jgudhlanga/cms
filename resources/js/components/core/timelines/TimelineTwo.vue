<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TimelineStep } from '@/types/utils';

interface Props {
    steps: TimelineStep[];
}

defineProps<Props>();
const statusClasses = {
    pending: 'bg-gray-500',
    completed: 'bg-green-500',
    active: 'bg-blue-500',
    failed: 'bg-red-500',
};

const svgIcons = {
    pending: `<svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path
                        fill-rule="nonzero"
                        d="M6 4.586L10.95.636a.914.914 0 0 1 1.294 1.294L7.294 5.88l4.95 4.95a.914.914 0 0 1-1.294 1.294L6 7.174l-4.95 4.95a.914.914 0 0 1-1.294-1.294L4.706 5.88.636 1.93A.914.914 0 0 1 1.93.636L6 4.586Z"
                    />
                </svg>`,
    completed: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check">
  <polyline points="20 6 9 17 4 12" />
</svg>
`,
    active: `<svg class="stroke-current fill-none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="8" stroke-width="2" />
                    <circle cx="10" cy="10" r="3" fill="currentColor" />
                </svg>`,
    failed: `<svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path
                        fill-rule="nonzero"
                        d="M6 4.586L10.95.636a.914.914 0 0 1 1.294 1.294L7.294 5.88l4.95 4.95a.914.914 0 0 1-1.294 1.294L6 7.174l-4.95 4.95a.914.914 0 0 1-1.294-1.294L4.706 5.88.636 1.93A.914.914 0 0 1 1.93.636L6 4.586Z"
                    />
                </svg>`,
};
</script>

<template>
    <div
        class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:h-full before:w-0.5 before:-translate-x-px before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent md:before:mx-auto md:before:translate-x-0"
    >
        <div
            v-for="(step, index) in steps"
            :key="index"
            class="group is-active relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse"
        >
            <!-- Icon -->
            <div
                :class="
                    cn(
                        'group-[.is-active]:bg-none flex h-10 w-10 shrink-0 items-center justify-center rounded-full ' +
                            ' border border-white bg-gray-500 text-gray-500 shadow group-[.is-active]:text-emerald-50 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2',
                        step.status ? statusClasses[step.status!] : '',
                    )
                "
            >
                <div v-html="svgIcons[step.status || 'active']"></div>
            </div>
            <!-- Card -->
            <div class="w-[calc(100%-4rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow md:w-[calc(50%-2.5rem)]">
                <div class="mb-1 flex items-center justify-between space-x-2">
                    <div class="text-sm font-semibold uppercase">{{ step.title }}</div>
                    <time class="font-caveat font-medium">{{ step.label }}</time>
                </div>
                <div class="text-muted-foreground">{{ step.description }}</div>
                <component :is="step.component" v-bind="step.props" />
            </div>
        </div>
    </div>
</template>
