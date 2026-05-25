<script setup lang="ts">
interface Props {
    label?: string;
    value?: string;
    labelClasses?: string;
    valueClasses?: string;
}
defineProps<Props>();
</script>
<template>
    <div class="flex flex-col gap-0.5">
    <span class="text-[0.65rem] font-semibold tracking-[0.1em] uppercase text-slate-400">{{ label }}</span>
    <span class="text-[0.85rem] font-bold text-slate-800 tracking-[-0.01em]">{{ !value ? '---' : value }}</span>
  </div>
</template>
 