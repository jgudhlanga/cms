<script setup lang="ts">
import { DepartmentDistribution } from '@/types/dasboard';
interface Props {
    departmentTableData: DepartmentDistribution[];
}
defineProps<Props>();
</script>

<template>
    <div class="rounded-lg bg-white px-4 py-2 shadow">
        <h3 class="mb-4 text-lg font-medium">Detailed View</h3>
        <div class="h-auto">
            <table class="j-table">
                <thead class="j-thead">
                    <tr class="j-th">
                        <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                        <th class="j-th text-center">{{ $tChoice('trans.application', 1) }}</th>
                        <th class="j-th text-center">Percentage</th>
                    </tr>
                </thead>
                <tbody class="j-tbody">
                    <tr class="j-tr" v-for="data in departmentTableData" :key="data.departmentId">
                        <td class="j-td flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: data?.color }"></span>
                            {{ data?.departmentName ?? '' }}
                        </td>
                        <td class="j-td text-center">{{ data.applicationCount }}</td>
                        <td class="j-td text-center">{{ data.percentage }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
