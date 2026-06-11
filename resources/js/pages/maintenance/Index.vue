<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useMaintenance } from '@/composables/maintenance/useMaintenance';
import MaintenanceShell from '@/pages/maintenance/partials/MaintenanceShell.vue';
import { useMaintenanceStore } from '@/store/maintenance/useMaintenanceStore';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import type { StaffImportResult } from '@/types/staff-import';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted, watch } from 'vue';

const props = defineProps<{
    staffImportResult?: StaffImportResult | null;
    exportCounts?: MaintenanceExportCounts;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.maintenance' }];

const { maintenanceTabs } = useMaintenance();
const maintenanceStore = useMaintenanceStore();
const { activeTab } = storeToRefs(maintenanceStore);

const visibleTabs = computed(() => maintenanceTabs().filter((tab) => tab.show));

onMounted(() => {
    if (props.staffImportResult) {
        activeTab.value = 'staff';
    }
});

watch(
    () => props.staffImportResult,
    (result) => {
        if (result) {
            activeTab.value = 'staff';
        }
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
