<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { PaymentGatewayFieldSource } from '@/types/integrations';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    source: PaymentGatewayFieldSource;
}>();

/**
 * Dot + short label. The dot carries the colour so the chip stays narrow enough
 * to sit on the label line, and the title repeats it as words for the colour blind.
 */
const styles: Record<PaymentGatewayFieldSource, { chip: string; dot: string; label: string; hint: string }> = {
    database: {
        chip: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
        dot: 'bg-emerald-500',
        label: 'integrations.source_database',
        hint: 'integrations.source_database_hint',
    },
    env: {
        chip: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400',
        dot: 'bg-sky-500',
        label: 'integrations.source_env',
        hint: 'integrations.source_env_hint',
    },
    missing: {
        chip: 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-400',
        dot: 'bg-amber-500',
        label: 'integrations.source_missing',
        hint: 'integrations.source_missing_hint',
    },
};

const style = computed(() => styles[props.source] ?? styles.missing);
</script>

<template>
    <span
        :class="cn('inline-flex shrink-0 items-center gap-1 rounded-full border px-1.5 py-px text-[10px] leading-4 font-semibold', style.chip)"
        :title="trans(style.hint)"
    >
        <span :class="cn('size-1.5 shrink-0 rounded-full', style.dot)" aria-hidden="true" />
        {{ trans(style.label) }}
    </span>
</template>
