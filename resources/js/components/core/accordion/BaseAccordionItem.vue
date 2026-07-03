<script setup lang="ts">
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
            class="group cursor-pointer gap-2 px-3 py-3 hover:no-underline sm:px-4 [&[data-state=open]_.accordion-chevron]:rotate-180 [&>svg]:ml-auto"
        >
            <div class="flex min-w-0 flex-1 flex-col items-start gap-1 text-left">
                <div class="flex w-full min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                    <span class="wrap-break-word text-left text-xs font-bold uppercase text-accent-foreground">{{ title }}</span>
                    <slot name="trigger-extra" />
                </div>
                <p
                    v-if="description"
                    class="text-left text-xs leading-relaxed text-muted-foreground"
                >
                    {{ description }}
                </p>
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
        <AccordionContent class="px-2 pb-4 pt-1 sm:px-4">
            <slot />
        </AccordionContent>
    </AccordionItem>
</template>
