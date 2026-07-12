<script setup lang="ts">
import AccommodationSectionEmpty from '@/components/students/accommodation/AccommodationSectionEmpty.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import type { HostelQuery } from '@/types/hms';
import { Inbox } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    queries: HostelQuery[];
    isLoading: boolean;
    canCreate?: boolean;
    context?: 'admin' | 'portal';
}

const props = withDefaults(defineProps<Props>(), {
    canCreate: false,
    context: 'admin',
});

const openQueries = computed(() =>
    props.queries.filter((q) => ['open', 'in-progress'].includes(q.attributes.status)),
);

const isPortal = computed(() => props.context === 'portal');

function priorityClass(p: string): string {
    if (p === 'high') {
        return 'bg-destructive text-destructive-foreground';
    }

    if (p === 'medium') {
        return 'bg-amber-500/90 text-white dark:bg-amber-600';
    }

    return 'bg-muted text-muted-foreground';
}

function openCreateModal(): void {
    openModal({ name: APP_MODULE_KEYS.hostel_accommodation_query });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-if="!isPortal"
            class="flex flex-wrap items-center justify-between gap-2"
        >
            <p class="text-left text-sm text-muted-foreground">
                {{ $t('students.accommodation_queries_count', { count: queries.length }) }}
            </p>
            <BaseButton
                v-if="canCreate"
                :color="ColorVariant.primary"
                :size="ButtonSize.sm"
                @click="openCreateModal"
            >
                {{ $t('hms.new_query') }}
            </BaseButton>
        </div>

        <DataLoadingSpinner v-if="isLoading" />

        <AccommodationSectionEmpty
            v-else-if="queries.length === 0"
            :icon="Inbox"
            :message="$t('students.accommodation_no_queries')"
            :action-label="canCreate ? $t('hms.new_query') : null"
            @action="openCreateModal"
        />

        <template v-else>
            <div
                v-if="isPortal && canCreate"
                class="flex justify-end"
            >
                <BaseButton
                    :color="ColorVariant.primary"
                    :size="ButtonSize.sm"
                    @click="openCreateModal"
                >
                    {{ $t('hms.new_query') }}
                </BaseButton>
            </div>

            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[600px] text-left text-sm">
                    <thead class="border-b border-border bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ $tChoice('trans.subject', 1) }}</th>
                            <th class="px-3 py-2 text-left">{{ $t('trans.category') }}</th>
                            <th class="px-3 py-2 text-left">{{ $t('trans.priority') }}</th>
                            <th class="px-3 py-2 text-left">{{ $tChoice('trans.status', 1) }}</th>
                            <th class="px-3 py-2 text-left">{{ $t('trans.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="query in queries"
                            :key="query.id"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-3 py-2 font-medium text-foreground">{{ query.attributes.subject }}</td>
                            <td class="px-3 py-2 text-muted-foreground">{{ query.attributes.categoryLabel }}</td>
                            <td class="px-3 py-2">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="priorityClass(query.attributes.priority)"
                                >
                                    {{ query.attributes.priorityLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ query.attributes.statusLabel }}</td>
                            <td class="px-3 py-2 text-muted-foreground">{{ query.attributes.createdAt?.slice(0, 10) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <p v-if="openQueries.length" class="text-left text-xs text-muted-foreground">
            {{ $t('students.accommodation_open_queries', { count: openQueries.length }) }}
        </p>
    </div>
</template>
