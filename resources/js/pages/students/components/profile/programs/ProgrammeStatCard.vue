<script setup lang="ts">
interface Props {
    labelKey: string;
    value: string | number;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl border border-stone-100 bg-stone-50 px-4 py-3">
        <p class="mb-1 text-[0.65rem] font-medium text-slate-400">
            {{ $t(labelKey) }}
        </p>
        <p class="text-[0.95rem] font-bold tracking-tight text-slate-800">
            {{ value }}
        </p>
    </div>
</template>
