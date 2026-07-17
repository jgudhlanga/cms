<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import DashboardCard from '@/pages/dashboard/components/DashboardCard.vue';
import type { LecturerModuleRow } from '@/types/lecturer';
import type { AcademicCalendar } from '@/types/academic-calendar';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    modules: LecturerModuleRow[];
    academicCalendar: AcademicCalendar;
    academicContextSubtitle: string;
}

defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { title: trans('dashboard.lecturer_dashboard_title'), href: route('lecturer.dashboard') },
    { title: trans_choice('trans.module', 2) },
]);
</script>

<template>
    <Head :title="$tChoice('trans.module', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <ComponentHeader
                :header-title="$tChoice('trans.module', 2)"
                :description="academicContextSubtitle"
            />

            <DashboardCard :title="$t('dashboard.lecturer_modules')">
                <Empty
                    v-if="modules.length === 0"
                    :message="$t('dashboard.lecturer_no_modules')"
                />
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-muted-foreground">
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.module', 1) }}</th>
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.code', 1) }}</th>
                                <th class="py-2 pr-3 font-medium">{{ $tChoice('trans.department', 1) }}</th>
                                <th class="py-2 font-medium">{{ $tChoice('trans.class', 2) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in modules"
                                :key="row.id"
                                class="border-b border-border/60 last:border-0"
                            >
                                <td class="py-2 pr-3 font-medium text-foreground">{{ row.title }}</td>
                                <td class="py-2 pr-3">{{ row.code || '—' }}</td>
                                <td class="py-2 pr-3">{{ row.departmentName || '—' }}</td>
                                <td class="py-2">
                                    <span v-if="row.classes.length === 0">—</span>
                                    <span v-else>{{
                                        row.classes.map((item) => item.name).join(', ')
                                    }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DashboardCard>
        </div>
    </PageContainer>
</template>
