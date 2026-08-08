<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    source?: 'bank' | 'online' | 'assessed' | string | null;
}

const props = withDefaults(defineProps<Props>(), {
    source: 'bank',
});

const normalizedSource = computed(() => String(props.source || 'bank').toLowerCase());

const labelKey = computed(() => {
    if (normalizedSource.value === 'online') {
        return 'finance.source_online';
    }

    if (normalizedSource.value === 'assessed') {
        return 'finance.source_assessed';
    }

    return 'finance.source_bank';
});

const badgeClass = computed(() => {
    if (normalizedSource.value === 'online') {
        return 'bg-sky-500/15 text-sky-700 dark:text-sky-400 ring-1 ring-sky-500/30';
    }

    if (normalizedSource.value === 'assessed') {
        return 'bg-violet-500/15 text-violet-700 dark:text-violet-400 ring-1 ring-violet-500/30';
    }

    return 'bg-slate-500/15 text-slate-700 dark:text-slate-300 ring-1 ring-slate-500/30';
});
</script>

<template>
    <span :class="badgeClass" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">
        {{ $t(labelKey) }}
    </span>
</template>
