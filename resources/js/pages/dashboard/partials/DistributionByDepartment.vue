<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { DepartmentDistribution } from '@/types/dasboard';
import { computed } from 'vue';

interface Props {
    departmentDistribution: DepartmentDistribution[];
}

const props = defineProps<Props>();
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
const totalApplications = computed(
    () => departmentDistribution?.reduce((sum, d) => sum + d.applicationCount, 0) ?? 0
);

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
            total: 0,
        }
    );
});

</script>

<template>
    <div class="rounded-lg bg-white px-4 py-2 shadow">
        <div class="mb-4 text-lg font-medium">
            <HeadingSmall title="Distribution by Department" />
        </div>
        <div class="h-auto">
            <table class="j-table">
                <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                    <th class="j-th text-center">Males</th>
                    <th class="j-th text-center">Females</th>
                    <th class="j-th text-center">Disabled</th>
                    <th class="j-th text-center">Full Time</th>
                    <th class="j-th text-center">Part Time</th>
                    <th class="j-th text-center">Block</th>
                    <th class="j-th text-center">Ojet</th>
                    <th class="j-th text-center">Total</th>
                    <th class="j-th text-center">%age</th>
                </tr>
                </thead>
                <tbody class="j-tbody">
                <tr class="j-tr" v-for="data in departmentTableData" :key="data.departmentId">
                    <td class="j-td flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: data.color }"></span>
                        {{ data.departmentName }}
                    </td>
                    <td class="j-td text-center">{{ data.maleCount }}</td>
                    <td class="j-td text-center">{{ data.femaleCount }}</td>
                    <td class="j-td text-center">{{ data.disabledCount }}</td>
                    <td class="j-td text-center">{{ data.fullTimeCount }}</td>
                    <td class="j-td text-center">{{ data.partTimeCount }}</td>
                    <td class="j-td text-center">{{ data.blockReleaseCount }}</td>
                    <td class="j-td text-center">{{ data.ojetCount }}</td>
                    <td class="j-td text-center font-medium">{{ data.applicationCount }}</td>
                    <td class="j-td text-center">{{ data.percentage }}</td>
                </tr>

                <!-- Totals Row -->
                <tr class="j-tr font-semibold bg-gray-50">
                    <td class="j-td text-left">Total</td>
                    <td class="j-td text-center">{{ departmentTotals.male }}</td>
                    <td class="j-td text-center">{{ departmentTotals.female }}</td>
                    <td class="j-td text-center">{{ departmentTotals.disabled }}</td>
                    <td class="j-td text-center">{{ departmentTotals.fullTime }}</td>
                    <td class="j-td text-center">{{ departmentTotals.partTime }}</td>
                    <td class="j-td text-center">{{ departmentTotals.block }}</td>
                    <td class="j-td text-center">{{ departmentTotals.ojet }}</td>
                    <td class="j-td text-center">{{ departmentTotals.total }}</td>
                    <td class="j-td text-center">100%</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
