<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

const noticeCount = 2;
</script>

<template>
    <Collapsible v-slot="{ open }" :default-open="true" class="mb-4">
        <CollapsibleTrigger
            class="flex w-full items-center justify-between gap-2 rounded-md border border-border/80 bg-muted/30 px-2.5 py-1.5 text-left text-[10px] font-medium uppercase text-foreground transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:text-[11px]"
        >
            <span class="truncate">
                {{ $t('trans.enrollment_important_notices') }}
                <span class="text-muted-foreground">({{ noticeCount }})</span>
            </span>
            <ChevronDown
                :class="
                    cn('size-3.5 shrink-0 text-muted-foreground transition-transform duration-200', open && 'rotate-180')
                "
                aria-hidden="true"
            />
        </CollapsibleTrigger>

        <CollapsibleContent class="pt-1.5">
            <ul class="space-y-1 border-l-2 border-border pl-2.5 text-[10px] leading-snug uppercase sm:text-[11px]">
                <li class="text-amber-700 dark:text-amber-400/90">
                    {{ $t('trans.ui_ecocash_users_payment_device_warning') }}
                </li>
                <li class="font-semibold text-red-700 dark:text-red-400/90">
                    {{ $t('trans.ui_college_advert_warning') }}
                </li>
            </ul>
        </CollapsibleContent>
    </Collapsible>
</template>
