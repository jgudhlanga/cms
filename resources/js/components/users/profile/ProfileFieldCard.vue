<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    label: string;
    value?: string;
    isEmpty?: boolean;
    emptyLabel?: string;
}

withDefaults(defineProps<Props>(), {
    value: '',
    isEmpty: false,
    emptyLabel: '',
});
</script>

<template>
    <div class="rounded-xl border border-border/60 bg-muted/30 px-4 py-3.5">
        <span class="text-[0.63rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
            {{ label }}
        </span>
        <p
            :class="
                cn(
                    'mt-1 text-[0.9rem] font-bold tracking-[-0.01em]',
                    isEmpty ? 'italic font-normal text-muted-foreground' : 'text-foreground',
                )
            "
        >
            {{ isEmpty ? emptyLabel : value }}
        </p>
    </div>
</template>
