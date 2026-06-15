<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import type { HostelLeave } from '@/types/hms';

interface Props {
    leaves: HostelLeave[];
    isLoading: boolean;
    canCreate?: boolean;
}

withDefaults(defineProps<Props>(), {
    canCreate: false,
});

function statusClass(status: string): string {
    if (status === 'approved') {
        return 'bg-primary text-primary-foreground';
    }

    if (status === 'declined') {
        return 'bg-destructive text-destructive-foreground';
    }

    return 'bg-muted text-muted-foreground';
}

function openCreateModal(): void {
    openModal({ name: APP_MODULE_KEYS.hostel_accommodation_leave });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-left text-sm text-muted-foreground">
                {{ $t('students.accommodation_leaves_count', { count: leaves.length }) }}
            </p>
            <BaseButton
                v-if="canCreate"
                :color="ColorVariant.primary"
                :size="ButtonSize.sm"
                @click="openCreateModal"
            >
                {{ $t('hms.apply_for_leave') }}
            </BaseButton>
        </div>

        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="leaves.length === 0"
            class="rounded-xl border border-dashed border-border py-8 text-center text-sm text-muted-foreground"
        >
            {{ $t('students.accommodation_no_leaves') }}
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-border bg-muted/40 text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-3 py-2 text-left">{{ $tChoice('trans.type', 1) }}</th>
                        <th class="px-3 py-2 text-left">{{ $t('trans.from') }}</th>
                        <th class="px-3 py-2 text-left">{{ $t('trans.to') }}</th>
                        <th class="px-3 py-2 text-left">{{ $tChoice('trans.status', 1) }}</th>
                        <th class="px-3 py-2 text-left">{{ $t('students.accommodation_reviewed_by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="leave in leaves"
                        :key="leave.id"
                        class="border-b border-border last:border-0"
                    >
                        <td class="px-3 py-2 font-medium text-foreground">{{ leave.attributes.leaveType }}</td>
                        <td class="px-3 py-2">{{ leave.attributes.fromDate?.slice(0, 10) }}</td>
                        <td class="px-3 py-2">{{ leave.attributes.toDate?.slice(0, 10) }}</td>
                        <td class="px-3 py-2">
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusClass(leave.attributes.status)"
                            >
                                {{ leave.attributes.statusLabel }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            <template v-if="leave.attributes.reviewedByName">
                                {{ $t('students.accommodation_by_reviewer', { name: leave.attributes.reviewedByName }) }}
                            </template>
                            <template v-else>—</template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
