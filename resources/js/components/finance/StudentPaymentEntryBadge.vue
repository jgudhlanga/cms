<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    isCharge: boolean;
}

const props = defineProps<Props>();

const badgeClass = computed(() =>
    props.isCharge
        ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400 ring-1 ring-amber-500/30'
        : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-500/30',
);
</script>

<template>
    <span :class="badgeClass" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">
        {{ isCharge ? $t('finance.charge') : $t('finance.payment') }}
    </span>
</template>
