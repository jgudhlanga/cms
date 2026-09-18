<script setup lang="ts">
import { computed } from 'vue';
import { type Tone, toneGradient, toneSolid } from './tones';

interface Props {
    label: string;
    value: string | number;
    percent: number;
    tone?: Tone;
    /** `bar` draws a track + fill; `inline` fills the row background behind the text. */
    variant?: 'bar' | 'inline';
    labelWidthClass?: string;
    valueWidthClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'indigo',
    variant: 'bar',
    labelWidthClass: 'w-24',
    valueWidthClass: 'w-12',
});

const width = computed(() => `${Math.min(100, Math.max(0, props.percent))}%`);
</script>

<template>
    <div v-if="variant === 'bar'" class="group/row">
        <div class="flex items-center gap-2.5">
            <div
                class="shrink-0 truncate text-[11px] leading-tight text-muted-foreground transition-colors group-hover/row:text-foreground"
                :class="labelWidthClass"
            >
                {{ label }}
            </div>
            <div class="relative h-2 flex-1 overflow-hidden rounded-full bg-muted">
                <div
                    class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 ease-out"
                    :class="toneGradient[tone]"
                    :style="{ width }"
                />
            </div>
            <div
                class="shrink-0 text-right text-[11px] font-semibold tabular-nums tracking-tight text-foreground"
                :class="valueWidthClass"
            >
                {{ value }}
            </div>
        </div>
    </div>

    <div
        v-else
        class="group/row relative flex items-center justify-between gap-2 overflow-hidden rounded-md px-1.5 py-1 transition-colors hover:bg-muted/40"
    >
        <div
            class="absolute inset-y-0 left-0 rounded-md opacity-[0.15] transition-all duration-500 ease-out"
            :class="toneSolid[tone]"
            :style="{ width }"
        />
        <span class="relative truncate text-[11px] leading-tight text-foreground">{{ label }}</span>
        <span class="relative shrink-0 text-[11px] font-semibold tabular-nums tracking-tight text-foreground">
            {{ value }}
        </span>
    </div>
</template>
