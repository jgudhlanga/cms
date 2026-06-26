<script setup lang="ts">
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';


interface Props {
    label: string;
    value: string;
    icon?: IconName ;
}
defineProps<Props>();
</script>
<template>
  <div class="flex items-start gap-3 rounded-xl border border-border bg-card px-4 py-3.5 transition-colors duration-150 hover:bg-muted/50">
    <div class="mt-0.5 h-[18px] w-[18px] shrink-0 text-muted-foreground">
        <component v-if="icon" :is="icons[icon]" class="h-4 w-4" />
    </div>
    <div class="flex min-w-0 flex-col gap-0.5">
        <span class="text-[0.63rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground">{{ label }}</span>
        <span class="break-words whitespace-pre-line text-[0.85rem] font-bold tracking-[-0.01em] text-foreground">{{ value }}</span>
    </div>
  </div>
</template>