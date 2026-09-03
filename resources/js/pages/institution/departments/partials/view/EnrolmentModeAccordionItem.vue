<script setup lang="ts">
import { AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    value: string;
    title: string;
    count: number;
    programmeCount: number;
    loaded?: boolean;
    icon?: IconName;
    isOpen?: boolean;
    emptySubtitle?: string;
    countSingular?: string;
    countPlural?: string;
    groupSingular?: string;
    groupPlural?: string;
}

const props = withDefaults(defineProps<Props>(), {
    icon: IconName.graduation_cape,
    isOpen: false,
    loaded: false,
    emptySubtitle: 'No applications recorded',
    countSingular: 'application',
    countPlural: 'applications',
    groupSingular: 'programme',
    groupPlural: 'programmes',
});

const emit = defineEmits<{
    open: [];
}>();

const isEmpty = computed(() => props.count === 0);

const subtitle = computed(() => {
    if (isEmpty.value) {
        return props.emptySubtitle;
    }

    if (!props.loaded) {
        return props.count === 1 ? `1 ${props.countSingular}` : `${props.count} ${props.countPlural}`;
    }

    const label = props.programmeCount === 1 ? props.groupSingular : props.groupPlural;

    return `${props.programmeCount} ${label}`;
});

const iconBoxClass = computed(() =>
    cn(
        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors',
        isEmpty.value ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary',
        props.isOpen && !isEmpty.value ? 'bg-primary/15' : '',
    ),
);

const itemClass = computed(() =>
    cn(
        'rounded-xl border bg-card shadow-sm transition-[border-color,box-shadow]',
        props.isOpen ? 'overflow-visible border-primary/40 shadow-md' : 'overflow-hidden border-border/70 hover:border-primary/25',
        isEmpty.value ? 'opacity-90' : '',
    ),
);
</script>

<template>
    <AccordionItem :value="value" :class="itemClass">
        <AccordionTrigger
            class="group cursor-pointer gap-2.5 px-3 py-3 hover:no-underline sm:px-4 [&[data-state=open]_.accordion-chevron]:rotate-180 [&>svg]:hidden"
            @click="emit('open')"
        >
            <div class="flex min-w-0 flex-1 items-center gap-2.5 text-left">
                <span :class="iconBoxClass" aria-hidden="true">
                    <component :is="icons[icon]" class="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-foreground">{{ title }}</p>
                    <p class="truncate text-[11px] text-muted-foreground">{{ subtitle }}</p>
                </div>
                <span
                    class="shrink-0 text-base font-bold tabular-nums"
                    :class="isEmpty ? 'text-muted-foreground' : 'text-foreground'"
                >
                    {{ count }}
                </span>
                <span
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors group-hover:bg-muted"
                    aria-hidden="true"
                >
                    <ChevronDown class="accordion-chevron h-4 w-4 transition-transform duration-200" stroke-width="2.25" />
                </span>
            </div>
            <template #actions>
                <slot name="header-actions" />
            </template>
        </AccordionTrigger>
        <AccordionContent class="px-3 pb-3 pt-0 sm:px-4">
            <slot />
        </AccordionContent>
    </AccordionItem>
</template>
