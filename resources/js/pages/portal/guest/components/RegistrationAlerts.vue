<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

const noticeCount = 2;
</script>

<template>
    <Collapsible v-slot="{ open }" :default-open="false" class="mb-4">
        <CollapsibleTrigger
            class="flex w-full items-center justify-between gap-2 rounded-md border border-border/80 bg-muted/30 px-3 py-2 text-left text-sm font-medium text-foreground transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        >
            <span class="truncate">
                {{ $t('trans.enrollment_important_notices') }}
                <span class="text-muted-foreground">({{ noticeCount }})</span>
            </span>
            <ChevronDown
                :class="cn('size-4 shrink-0 text-muted-foreground transition-transform duration-200', open && 'rotate-180')"
                aria-hidden="true"
            />
        </CollapsibleTrigger>

        <CollapsibleContent class="pt-2">
            <ul class="space-y-2 rounded-md border border-border/60 bg-muted/20 p-3 text-sm leading-relaxed text-muted-foreground">
                <li>{{ $t('trans.ui_ecocash_users_payment_device_warning') }}</li>
                <li class="font-medium text-foreground">{{ $t('trans.ui_college_advert_warning') }}</li>
            </ul>
        </CollapsibleContent>
    </Collapsible>
</template>
