<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import DepartmentClassListActionLink from '@/components/enrolments/DepartmentClassListActionLink.vue';
import DepartmentDistributionExpandedDetail from '@/components/enrolments/partials/DepartmentDistributionExpandedDetail.vue';
import DepartmentDistributionKpiCards from '@/components/enrolments/partials/DepartmentDistributionKpiCards.vue';
import DepartmentDistributionToolbar from '@/components/enrolments/partials/DepartmentDistributionToolbar.vue';
import {
    buildDepartmentDistributionKpis,
    presentDepartmentDistributionRows,
    rowKey,
    sumDepartmentDistribution,
    type DepartmentDistributionRow,
    type DepartmentDistributionSortKey,
} from '@/lib/departmentDistributionPresentation';
import { canOpenEnrolmentStatusList } from '@/lib/enrolmentStatusNavigation';
import { buildDepartmentApplicationsUrl, type EnrolmentStatusFrom } from '@/lib/enrolmentStatusOrigin';
import { IconName, icons } from '@/lib/icons';
import { getUserAbilities } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import type { DepartmentDistribution } from '@/types/dashboard';
import type { IntakePeriod } from '@/types/institution';
import type { SelectOption } from '@/types/utils';
import { ArrowDownWideNarrow, ChevronDown, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    departmentDistribution: DepartmentDistribution[];
    showActionsColumn?: boolean;
    showFilters?: boolean;
    showSummaryCards?: boolean;
    intakePeriods?: IntakePeriod[];
    handleFilterChange?: (option: SelectOption) => void;
    origin?: EnrolmentStatusFrom;
}

const props = withDefaults(defineProps<Props>(), {
    showActionsColumn: false,
    showFilters: false,
    showSummaryCards: false,
    origin: 'enrolments',
});

const intakePeriodModel = defineModel<SelectOption | null>('intakePeriodModel');

const search = ref('');
const sortKey = ref<DepartmentDistributionSortKey>('name_asc');
const expandedRowKey = ref<string | null>(null);

const kpis = computed(() => buildDepartmentDistributionKpis(props.departmentDistribution ?? []));
const totals = computed(() => sumDepartmentDistribution(props.departmentDistribution ?? []));
const tableRows = computed(() =>
    presentDepartmentDistributionRows(props.departmentDistribution ?? [], search.value, sortKey.value),
);

const intakePeriodId = computed(() =>
    intakePeriodModel.value?.value != null ? String(intakePeriodModel.value.value) : undefined,
);

watch(
    () => props.departmentDistribution,
    () => {
        expandedRowKey.value = null;
    },
);

const toggleRow = (key: string) => {
    expandedRowKey.value = expandedRowKey.value === key ? null : key;
};

const isExpanded = (key: string) => expandedRowKey.value === key;

const setSort = (next: DepartmentDistributionSortKey) => {
    sortKey.value = next;
};

const canOpenStatusList = (row: DepartmentDistributionRow, type: string) =>
    canOpenEnrolmentStatusList(type, props.showActionsColumn, row.institutionDepartmentId, getUserAbilities());

const statusRoute = (row: DepartmentDistributionRow, type: string) =>
    buildDepartmentApplicationsUrl({
        institutionDepartmentId: row.institutionDepartmentId,
        intakePeriodId: intakePeriodId.value,
        type,
        from: props.origin,
    });

const onIntakeChange = (option: SelectOption | null) => {
    if (!option || !props.handleFilterChange) {
        return;
    }
    props.handleFilterChange(option);
};
</script>

<template>
    <div class="space-y-2.5 rounded-lg border border-border bg-card p-3 text-card-foreground shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold tracking-tight text-foreground">
                    {{ $t('trans.ui_distribution_by_department') }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    {{
                        $t('trans.ui_applications_tracked_across_departments', {
                            applications: kpis.totalApplications.toLocaleString(),
                            departments: String(kpis.assignedDepartmentCount),
                        })
                    }}
                </p>
            </div>
            <div
                v-if="showFilters"
                class="flex min-w-0 shrink-0 items-center gap-1.5 rounded-md border border-border/60 bg-muted/20 px-2 py-1 sm:min-w-[240px] sm:max-w-sm"
            >
                <component :is="icons[IconName.calendar]" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                <span class="shrink-0 text-xs font-medium text-muted-foreground">{{ $tChoice('trans.intake_period', 1) }}</span>
                <IntakePeriodComboSelect
                    :data="intakePeriods ?? []"
                    label=""
                    v-model="intakePeriodModel"
                    :vertical-layout="false"
                    :is-required="true"
                    width-class="w-full"
                    class="min-w-0 flex-1"
                    @update:modelValue="onIntakeChange"
                />
            </div>
        </div>

        <DepartmentDistributionKpiCards v-if="showSummaryCards" :kpis="kpis" />

        <DepartmentDistributionToolbar v-model:search="search" v-model:sort-key="sortKey" />

        <div class="overflow-x-auto rounded-md border border-border/70">
            <table class="w-full min-w-175 border-collapse text-xs">
                <thead>
                    <tr class="border-b border-border bg-muted/40 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                        <th class="px-2 py-1.5 text-left">{{ $tChoice('trans.department', 1) }}</th>
                        <th class="px-2 py-1.5 text-right">
                            <button
                                type="button"
                                class="inline-flex items-center gap-0.5 hover:text-foreground"
                                @click="setSort('total_desc')"
                            >
                                {{ $t('trans.ui_total') }}
                                <ArrowDownWideNarrow
                                    class="h-3 w-3"
                                    :class="sortKey === 'total_desc' ? 'text-foreground' : 'opacity-40'"
                                />
                            </button>
                        </th>
                        <th class="px-2 py-1.5 text-left">{{ $t('trans.ui_share_of_intake') }}</th>
                        <th
                            class="border-l border-border/70 px-1.5 py-1.5 text-center"
                            :title="$t('trans.provisional')"
                        >
                            {{ $t('trans.ui_status_abbr_provisional') }}
                        </th>
                        <th class="px-1.5 py-1.5 text-center" :title="$t('trans.ui_waitlist')">
                            {{ $t('trans.ui_status_abbr_waitlist') }}
                        </th>
                        <th class="px-1.5 py-1.5 text-center" :title="$t('trans.ui_rejected')">
                            {{ $t('trans.ui_status_abbr_rejected') }}
                        </th>
                        <th class="px-1.5 py-1.5 text-center" :title="$t('trans.ui_verified')">
                            {{ $t('trans.ui_status_abbr_verified') }}
                        </th>
                        <th class="px-1.5 py-1.5 text-center" :title="$t('trans.ui_final')">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-0.5 hover:text-foreground"
                                @click="setSort('final_desc')"
                            >
                                {{ $t('trans.ui_status_abbr_final') }}
                                <ArrowDownWideNarrow
                                    class="h-3 w-3"
                                    :class="sortKey === 'final_desc' ? 'text-foreground' : 'opacity-40'"
                                />
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="tableRows.length > 0">
                        <template v-for="(row, index) in tableRows" :key="rowKey(row)">
                            <tr
                                :class="
                                    cn(
                                        'border-b border-border/50 transition-colors hover:bg-muted/30',
                                        index % 2 === 1 && 'bg-muted/10',
                                        isExpanded(rowKey(row)) && 'bg-muted/25',
                                    )
                                "
                            >
                                <td class="px-2 py-1.5">
                                    <button
                                        type="button"
                                        class="group flex max-w-full items-center gap-1.5 text-left"
                                        :aria-expanded="isExpanded(rowKey(row))"
                                        @click="toggleRow(rowKey(row))"
                                    >
                                        <span
                                            class="inline-block h-2.5 w-2.5 shrink-0 rounded-[2px]"
                                            :style="{ backgroundColor: row.color }"
                                            aria-hidden="true"
                                        />
                                        <span class="truncate text-xs font-medium text-foreground group-hover:text-primary">
                                            {{ row.departmentName }}
                                        </span>
                                        <ChevronDown
                                            v-if="isExpanded(rowKey(row))"
                                            class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                                        />
                                        <ChevronRight v-else class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                    </button>
                                </td>
                                <td class="px-2 py-1.5 text-right text-sm font-semibold tabular-nums text-foreground">
                                    {{ row.applicationCount.toLocaleString() }}
                                </td>
                                <td class="min-w-27.5 px-2 py-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1 min-w-0 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full rounded-full"
                                                :style="{
                                                    width: `${Math.min(100, row.percentageValue)}%`,
                                                    backgroundColor: row.color,
                                                }"
                                            />
                                        </div>
                                        <div class="w-10 shrink-0 text-right text-[10px] tabular-nums text-muted-foreground">
                                            {{ row.percentage }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="border-l border-border/70 px-1 py-1.5 text-center" @click.stop>
                                    <DepartmentClassListActionLink
                                        :actionable="canOpenStatusList(row, 'provisional')"
                                        :title="row.provisionalCount.toLocaleString()"
                                        tone="provisional"
                                        :route-name="statusRoute(row, 'provisional')"
                                    />
                                </td>
                                <td class="px-1 py-1.5 text-center" @click.stop>
                                    <DepartmentClassListActionLink
                                        :actionable="false"
                                        :title="row.waitingCount.toLocaleString()"
                                        tone="waiting"
                                    />
                                </td>
                                <td class="px-1 py-1.5 text-center" @click.stop>
                                    <DepartmentClassListActionLink
                                        :actionable="false"
                                        :title="row.failedCount.toLocaleString()"
                                        tone="rejected"
                                        :flagged="row.isRejectionFlagged"
                                    />
                                </td>
                                <td class="px-1 py-1.5 text-center" @click.stop>
                                    <DepartmentClassListActionLink
                                        :actionable="canOpenStatusList(row, 'verified')"
                                        :title="row.verifiedCount.toLocaleString()"
                                        tone="verified"
                                        :route-name="statusRoute(row, 'verified')"
                                    />
                                </td>
                                <td class="px-1 py-1.5 text-center" @click.stop>
                                    <DepartmentClassListActionLink
                                        :actionable="canOpenStatusList(row, 'final')"
                                        :title="row.finalCount.toLocaleString()"
                                        tone="final"
                                        :route-name="statusRoute(row, 'final')"
                                    />
                                </td>
                            </tr>
                            <tr v-if="isExpanded(rowKey(row))" class="border-b border-border/50">
                                <td colspan="8" class="p-0">
                                    <DepartmentDistributionExpandedDetail
                                        :row="row"
                                        :show-actions-column="showActionsColumn"
                                        :intake-period-id="intakePeriodId"
                                        :origin="origin"
                                    />
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-else>
                        <td colspan="8" class="px-2 py-6 text-center text-xs text-muted-foreground">
                            {{
                                search.trim()
                                    ? $t('trans.ui_no_departments_match_search')
                                    : $t('trans.ui_no_department_distribution_data')
                            }}
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="(departmentDistribution?.length ?? 0) > 0">
                    <tr class="border-t border-border bg-sky-50/80 text-xs font-semibold dark:bg-sky-950/30">
                        <td class="px-2 py-1.5 text-left">{{ $t('trans.ui_total') }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ totals.total.toLocaleString() }}</td>
                        <td class="px-2 py-1.5 text-[10px] text-muted-foreground">{{ $t('trans.ui_100') }}</td>
                        <td class="border-l border-border/70 px-1 py-1.5 text-center tabular-nums">
                            {{ totals.provisional.toLocaleString() }}
                        </td>
                        <td class="px-1 py-1.5 text-center tabular-nums">{{ totals.waiting.toLocaleString() }}</td>
                        <td class="px-1 py-1.5 text-center tabular-nums">{{ totals.failed.toLocaleString() }}</td>
                        <td class="px-1 py-1.5 text-center tabular-nums">{{ totals.verified.toLocaleString() }}</td>
                        <td class="px-1 py-1.5 text-center tabular-nums text-emerald-700 dark:text-emerald-400">
                            {{ totals.final.toLocaleString() }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-col gap-0.5 text-[10px] leading-snug text-muted-foreground sm:flex-row sm:items-start sm:justify-between">
            <p>{{ $t('trans.ui_department_distribution_click_hint') }}</p>
            <p class="sm:text-right">{{ $t('trans.ui_rejection_rate_flag_hint') }}</p>
        </div>
    </div>
</template>
