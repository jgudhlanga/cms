<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ChevronDown } from 'lucide-vue-next';

interface Props {
    value: string;
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <AccordionItem
        :value="value"
        class="overflow-hidden rounded-lg border border-border border-b-0 bg-card shadow-sm"
    >
        <AccordionTrigger
            class="group cursor-pointer px-4 py-3 hover:no-underline [&[data-state=open]_.accordion-chevron]:rotate-180"
        >
            <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <HeadingSmall :title="title" :description="description" />
                <slot name="trigger-extra" />
            </div>
            <template #icon>
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-muted-foreground shadow-sm transition-all duration-200 group-hover:border-border group-hover:bg-accent group-hover:text-accent-foreground group-hover:shadow"
                    aria-hidden="true"
                >
                    <ChevronDown class="accordion-chevron h-4 w-4 transition-transform duration-200" stroke-width="2.25" />
                </span>
            </template>
        </AccordionTrigger>
        <AccordionContent class="px-4 pb-4 pt-1">
            <slot />
        </AccordionContent>
    </AccordionItem>
</template>
