<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useSectionTabQuerySync } from '@/composables/core/useSectionTabQuerySync';
import { useMaintenance } from '@/composables/maintenance/useMaintenance';
import MaintenanceShell from '@/pages/maintenance/partials/MaintenanceShell.vue';
import { useMaintenanceStore } from '@/store/maintenance/useMaintenanceStore';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import type { StaffImportResult } from '@/types/staff-import';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

const props = defineProps<{
    staffImportResult?: StaffImportResult | null;
    exportCounts?: MaintenanceExportCounts;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.maintenance' }];

const { maintenanceTabs } = useMaintenance();
const maintenanceStore = useMaintenanceStore();
const { activeTab } = storeToRefs(maintenanceStore);

const visibleTabs = computed(() => maintenanceTabs().filter((tab) => tab.show));

useSectionTabQuerySync(
    activeTab,
    () => visibleTabs.value.map((tab) => tab.value),
    {
        preferTab: () => (props.staffImportResult ? 'staff' : null),
    },
);
</script>

<template>
    <Head :title="$t('trans.maintenance')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <MaintenanceShell
            v-model:active-tab="activeTab"
            :tabs="visibleTabs"
            :staff-import-result="staffImportResult"
            :export-counts="exportCounts"
        />
    </PageContainer>
</template>
