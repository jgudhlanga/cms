<script setup lang="ts">
interface Props {
    labelKey: string;
    value: string;
}

defineProps<Props>();
</script>

<template>
    <div>
        <p class="mb-0.5 text-[0.62rem] font-semibold uppercase tracking-widest text-slate-400">
            {{ $t(labelKey) }}
        </p>
        <p class="text-[0.85rem] font-semibold text-slate-700">
            <slot>{{ value }}</slot>
        </p>
    </div>
</template>
