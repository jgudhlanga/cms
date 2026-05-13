<script setup lang="ts">
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';


interface Props {
    label: string;
    value: string;
    icon: IconName ;
}
defineProps<Props>();
</script>
<template>
  <div class="flex items-start gap-3 bg-stone-50 rounded-xl px-4 py-3.5 border border-stone-100 transition-colors duration-150 hover:bg-stone-100/70">
    <div class="w-[18px] h-[18px] flex-shrink-0 mt-0.5 text-slate-400">
        <component :is="icons[icon]" class="w-4 h-4" />
    </div>
    <div class="flex flex-col gap-0.5 min-w-0">
        <span class="text-[0.63rem] font-semibold tracking-[0.1em] uppercase text-slate-400">{{ label }}</span>
        <span class="text-[0.85rem] font-bold text-slate-800 tracking-[-0.01em] truncate">{{ value }}</span>
    </div>
  </div>
</template>