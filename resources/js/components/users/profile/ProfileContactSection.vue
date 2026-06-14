<script setup lang="ts">
import type { ProfileContactRow } from '@/composables/users/useUserProfileDetails';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { computed, ref } from 'vue';

interface Props {
    title: string;
    rows: ProfileContactRow[];
    emptyLabel: string;
    collapsedCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    collapsedCount: 2,
});

const expanded = ref(false);

const visibleRows = computed(() => {
    if (expanded.value || props.rows.length <= props.collapsedCount) {
        return props.rows;
    }

    return props.rows.slice(0, props.collapsedCount);
});

const canExpand = computed(() => props.rows.length > props.collapsedCount);
</script>

<template>
    <section class="space-y-3">
        <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
            {{ title }}
        </h2>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div
                v-for="(row, index) in visibleRows"
                :key="row.transKey"
                :class="cn('flex items-start gap-3 px-4 py-3.5', index > 0 && 'border-t border-border')"
            >
                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <component :is="icons[row.icon]" class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[0.63rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                        {{ $t(row.transKey) }}
                    </span>
                    <p
                        :class="
                            cn(
                                'mt-0.5 break-words text-[0.9rem] font-bold tracking-[-0.01em]',
                                row.isEmpty ? 'italic font-normal text-muted-foreground' : 'text-foreground',
                            )
                        "
                    >
                        {{ row.isEmpty ? emptyLabel : row.value }}
                    </p>
                </div>
            </div>

            <div v-if="canExpand" class="flex justify-center border-t border-border py-2">
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground"
                    :aria-expanded="expanded"
                    @click="expanded = !expanded"
                >
                    <component
                        :is="icons[IconName.chevron_down]"
                        :class="cn('h-4 w-4 transition-transform', expanded && 'rotate-180')"
                    />
                </button>
            </div>
        </div>
    </section>
</template>
