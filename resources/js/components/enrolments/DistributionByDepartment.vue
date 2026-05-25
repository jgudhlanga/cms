<script setup lang="ts">
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import DepartmentClassListActionLink from '@/components/enrolments/DepartmentClassListActionLink.vue';
import { hasAbility } from '@/lib/permissions';
import { DepartmentDistribution } from '@/types/dasboard';
import { IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { computed } from 'vue';

interface Props {
    departmentDistribution: DepartmentDistribution[];
    showActionsColumn?: boolean;
    showFilters?: boolean;
    intakePeriods?: IntakePeriod[];
    handleFilterChange?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
    showActionsColumn: false,
    showFilters: false,
});
const { departmentDistribution } = props;

// Generate consistent RGBA color from department name
const colorFromDepartment = (name: string, alpha = 0.7): string => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const r = (hash >> 16) & 255;
    const g = (hash >> 8) & 255;
    const b = hash & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

// Total number of applications across all departments
const totalApplications = computed(() => departmentDistribution?.reduce((sum, d) => sum + d.applicationCount, 0) ?? 0);

// Build per-department table data with color + percentage
const departmentTableData = computed(() => {
    const total = totalApplications.value || 0;
    return (
        departmentDistribution?.map((d) => {
            const color = colorFromDepartment(d.departmentName, 0.7);
            const percentage = total > 0 ? ((d.applicationCount / total) * 100).toFixed(1) : '0.0';
            return {
                ...d,
                color,
                percentage,
            };
        }) ?? []
    );
});

const departmentTotals = computed(() => {
    return departmentDistribution?.reduce(
        (acc, d) => {
            acc.male += Number(d.maleCount) || 0;
            acc.female += Number(d.femaleCount) || 0;
            acc.disabled += Number(d.disabledCount) || 0;
            acc.fullTime += Number(d.fullTimeCount) || 0;
            acc.partTime += Number(d.partTimeCount) || 0;
            acc.block += Number(d.blockReleaseCount) || 0;
            acc.ojet += Number(d.ojetCount) || 0;
            acc.total += Number(d.applicationCount) || 0;
            acc.intakeClassSizeTotal += Number(d.departmentIntakeClassSizeTotal) || 0;
            return acc;
        },
        {
            male: 0,
            female: 0,
            disabled: 0,
            fullTime: 0,
            partTime: 0,
            block: 0,
            ojet: 0,
            intakeClassSizeTotal: 0,
            total: 0,
        },
    );
});
const classListTotals = computed(() => {
    return departmentDistribution?.reduce(
        (acc, d) => {
            acc.provisional += Number(d.provisionalCount) || 0;
            acc.waiting += Number(d.waitingCount) || 0;
            acc.verified += Number(d.verifiedCount) || 0;
            acc.final += Number(d.finalCount) || 0;
            acc.failed += Number(d.failedCount) || 0;
            return acc;
        },
        {
            provisional: 0,
            waiting: 0,
            verified: 0,
            final: 0,
            failed: 0,
        },
    );
});
const intakePeriodModel = defineModel<SelectOption | null>('intakePeriodModel');
</script>

<template>
    <div class="rounded-lg bg-white px-4 py-2 shadow">
        <div class="mb-4 flex items-center justify-between text-lg font-medium">
            <HeadingSmall :title="$t('trans.ui_distribution_by_department')" />
            <IntakePeriodComboSelect
                v-if="showFilters"
                v-model="intakePeriodModel"
                :is-required="true"
                class="w-1/2"
                :label-uppercase="true"
                :vertical-layout="false"
                :data="intakePeriods ?? []"
                @update:modelValue="handleFilterChange"
            />
        </div>
        <div class="h-auto">
            <table class="j-table">
                <thead class="j-thead">
                    <tr class="j-tr">
                        <th colspan="11"></th>
                        <th
                            colspan="5"
                            class="bg-persian-200 text-accent-foreground text-uppercase j-td-l-border j-td-r-border px-3 py-2 text-center text-xs font-bold"
                        >
                            {{ $t('trans.ui_application_statuses') }}
                        </th>
                    </tr>
                    <tr class="j-th">
                        <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                        <th class="j-th text-center">{{ $tChoice('general.male', 2) }}</th>
                        <th class="j-th text-center">{{ $tChoice('general.female', 2) }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_disabled') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_full_time') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_part_time') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_block') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_ojet') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_total') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_class_sizes') }}</th>
                        <th class="j-th text-center">{{ $t('trans.ui_percentage') }}</th>
                        <th class="j-th j-td-l-border text-center">{{ $t('trans.provisional') }}</th>
                        <th class="j-th j-td-l-border text-center">{{ $t('trans.ui_waitlist') }}</th>
                        <th class="j-th j-td-l-border text-center">{{ $t('trans.ui_rejected') }}</th>
                        <th class="j-th j-td-l-border text-center">{{ $t('trans.ui_verified') }}</th>
                        <th class="j-th j-td-l-border j-td-r-border text-center">{{ $t('trans.ui_final') }}</th>
                    </tr>
                </thead>
                <tbody class="j-tbody">
                    <tr class="j-tr" v-for="data in departmentTableData" :key="data.departmentId">
                        <td class="j-td flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: data.color }"></span>
                            <span>{{ data.departmentName }}</span>
                        </td>
                        <td class="j-td text-center">{{ data.maleCount }}</td>
                        <td class="j-td text-center">{{ data.femaleCount }}</td>
                        <td class="j-td text-center">{{ data.disabledCount }}</td>
                        <td class="j-td text-center">{{ data.fullTimeCount }}</td>
                        <td class="j-td text-center">{{ data.partTimeCount }}</td>
                        <td class="j-td text-center">{{ data.blockReleaseCount }}</td>
                        <td class="j-td text-center">{{ data.ojetCount }}</td>
                        <td class="j-td text-center font-medium">{{ data.applicationCount }}</td>
                        <td class="j-td text-center font-medium">{{ data.departmentIntakeClassSizeTotal }}</td>
                        <td class="j-td text-center">{{ data.percentage }}</td>
                        <td class="j-td j-td-l-border text-center">
                            <DepartmentClassListActionLink
                                :actionable="hasAbility('verify:class-lists') && showActionsColumn"
                                :title="String(data.provisionalCount)"
                                :route-name="
                                    route('enrolments.department-applications', {
                                        institution_department: data.departmentId,
                                        intake_period_id: intakePeriodModel?.value.toString(),
                                        type: 'provisional',
                                    })
                                "
                            />
                        </td>
                        <td class="j-td j-td-l-border text-center">
                            <DepartmentClassListActionLink
                                :actionable="hasAbility('verify:class-lists') && showActionsColumn"
                                :title="String(data.waitingCount)"
                                :route-name="
                                    route('enrolments.department-applications', {
                                        institution_department: data.departmentId,
                                        intake_period_id: intakePeriodModel?.value.toString(),
                                        type: 'waiting',
                                    })
                                "
                            />
                        </td>
                        <td class="j-td j-td-l-border text-center">
                            <DepartmentClassListActionLink :actionable="false" :title="String(data.failedCount)" />
                        </td>
                        <td class="j-td j-td-l-border text-center">
                            <DepartmentClassListActionLink
                                :actionable="hasAbility('manage-final:class-lists') && showActionsColumn"
                                :title="String(data.verifiedCount)"
                                :route-name="
                                    route('enrolments.department-applications', {
                                        institution_department: data.departmentId,
                                        intake_period_id: intakePeriodModel?.value.toString(),
                                        type: 'verified',
                                    })
                                "
                            />
                        </td>
                        <td class="j-td j-td-l-border j-td-r-border text-center">
                            <DepartmentClassListActionLink
                                :actionable="hasAbility('manage-final:class-lists') && showActionsColumn"
                                :title="String(data.finalCount)"
                                :route-name="
                                    route('enrolments.department-applications', {
                                        institution_department: data.departmentId,
                                        intake_period_id: intakePeriodModel?.value.toString(),
                                        type: 'final',
                                    })
                                "
                            />
                        </td>
                    </tr>
                    <!-- Totals Row -->
                    <tr class="j-tr bg-gray-50 font-semibold">
                        <td class="j-td text-left">{{ $t('trans.ui_total') }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.male }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.female }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.disabled }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.fullTime }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.partTime }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.block }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.ojet }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.total }}</td>
                        <td class="j-td text-center">{{ departmentTotals?.intakeClassSizeTotal }}</td>
                        <td class="j-td text-center">{{ $t('trans.ui_100') }}</td>
                        <td class="j-td j-td-l-border j-td-b-border text-center">{{ classListTotals?.provisional }}</td>
                        <td class="j-td j-td-l-border j-td-b-border text-center">{{ classListTotals?.waiting }}</td>
                        <td class="j-td j-td-l-border j-td-b-border text-center">{{ classListTotals?.failed }}</td>
                        <td class="j-td j-td-l-border j-td-b-border text-center">{{ classListTotals?.verified }}</td>
                        <td class="j-td j-td-l-border j-td-r-border j-td-b-border text-center">{{ classListTotals?.final }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
