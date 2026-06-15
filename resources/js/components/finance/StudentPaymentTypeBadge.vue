<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    credit: number;
}

const props = defineProps<Props>();

const isCredit = computed(() => props.credit > 0);

const badgeClass = computed(() =>
    isCredit.value
        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500/30'
        : 'bg-rose-500/15 text-rose-700 dark:text-rose-400 ring-1 ring-rose-500/30',
);
</script>

<template>
    <span :class="badgeClass" class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase">
        {{ isCredit ? $t('finance.credit') : $t('finance.debit') }}
    </span>
</template>
