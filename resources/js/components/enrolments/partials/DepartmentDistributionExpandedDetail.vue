<script setup lang="ts">
import DepartmentClassListActionLink from '@/components/enrolments/DepartmentClassListActionLink.vue';
import type { DepartmentDistributionRow } from '@/lib/departmentDistributionPresentation';
import { enrollmentTypeShare } from '@/lib/departmentDistributionPresentation';
import { canOpenEnrolmentStatusList } from '@/lib/enrolmentStatusNavigation';
import { buildDepartmentApplicationsUrl, type EnrolmentStatusFrom } from '@/lib/enrolmentStatusOrigin';
import { getUserAbilities } from '@/lib/permissions';
import { computed } from 'vue';

interface Props {
    row: DepartmentDistributionRow;
    showActionsColumn: boolean;
    intakePeriodId?: string;
    origin?: EnrolmentStatusFrom;
}

const props = withDefaults(defineProps<Props>(), {
    origin: 'enrolments',
});

const canOpenStatusList = (type: string) =>
    canOpenEnrolmentStatusList(type, props.showActionsColumn, props.row.institutionDepartmentId, getUserAbilities());

const statusItems = computed(() => [
    {
        key: 'provisional',
        labelKey: 'trans.provisional',
        count: props.row.provisionalCount,
        tone: 'provisional' as const,
        actionable: canOpenStatusList('provisional'),
        type: 'provisional',
    },
    {
        key: 'waiting',
        labelKey: 'trans.ui_waitlist',
        count: props.row.waitingCount,
        tone: 'waiting' as const,
        actionable: false,
        type: null,
    },
    {
        key: 'failed',
        labelKey: 'trans.ui_rejected',
        count: props.row.failedCount,
        tone: 'rejected' as const,
        actionable: false,
        type: null,
        flagged: props.row.isRejectionFlagged,
    },
    {
        key: 'verified',
        labelKey: 'trans.ui_verified',
        count: props.row.verifiedCount,
        tone: 'verified' as const,
        actionable: canOpenStatusList('verified'),
        type: 'verified',
    },
    {
        key: 'final',
        labelKey: 'trans.ui_final',
        count: props.row.finalCount,
        tone: 'final' as const,
        actionable: canOpenStatusList('final'),
        type: 'final',
    },
]);

const enrollmentTypes = computed(() => {
    const total = props.row.applicationCount || 0;

    return [
        {
            key: 'full',
            labelKey: 'trans.ui_full_time',
            count: props.row.fullTimeCount,
            share: enrollmentTypeShare(props.row.fullTimeCount, total),
            barClass: 'bg-sky-500',
        },
        {
            key: 'part',
            labelKey: 'trans.ui_part_time',
            count: props.row.partTimeCount,
            share: enrollmentTypeShare(props.row.partTimeCount, total),
            barClass: 'bg-sky-500',
        },
        {
            key: 'block',
            labelKey: 'trans.ui_block',
            count: props.row.blockReleaseCount,
            share: enrollmentTypeShare(props.row.blockReleaseCount, total),
            barClass: 'bg-sky-500',
        },
        {
            key: 'ojet',
            labelKey: 'trans.ui_ojet',
            count: props.row.ojetCount,
            share: enrollmentTypeShare(props.row.ojetCount, total),
            barClass: 'bg-sky-500',
        },
    ];
});

const statusRoute = (type: string): string =>
    buildDepartmentApplicationsUrl({
        institutionDepartmentId: props.row.institutionDepartmentId,
        intakePeriodId: props.intakePeriodId,
        type,
        from: props.origin,
    });
</script>

<template>
    <div class="grid gap-2 border-t border-border/60 bg-muted/20 p-2 md:grid-cols-3">
        <div class="rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_demographics') }}
            </div>
            <dl class="space-y-0.5 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $tChoice('general.male', 1) }}</dt>
                    <dd class="font-medium text-foreground">{{ row.maleCount.toLocaleString() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $tChoice('general.female', 1) }}</dt>
                    <dd class="font-medium text-foreground">{{ row.femaleCount.toLocaleString() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.ui_disabled') }}</dt>
                    <dd class="font-medium text-foreground">{{ row.disabledCount.toLocaleString() }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_enrollment_type') }}
            </div>
            <div class="space-y-1.5">
                <div v-for="item in enrollmentTypes" :key="item.key" class="space-y-0.5">
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="text-muted-foreground">{{ $t(item.labelKey) }}</span>
                        <span class="font-medium text-foreground">{{ item.count.toLocaleString() }}</span>
                    </div>
                    <div class="h-1 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full transition-all" :class="item.barClass" :style="{ width: `${item.share}%` }" />
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_status_breakdown_click') }}
            </div>
            <div class="space-y-1" @click.stop>
                <div
                    v-for="item in statusItems"
                    :key="item.key"
                    class="flex items-center justify-between gap-2 text-xs"
                >
                    <span class="text-muted-foreground">{{ $t(item.labelKey) }}</span>
                    <DepartmentClassListActionLink
                        :actionable="item.actionable"
                        :title="item.count.toLocaleString()"
                        :tone="item.tone"
                        :flagged="Boolean(item.flagged)"
                        :route-name="item.type ? statusRoute(item.type) : undefined"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
