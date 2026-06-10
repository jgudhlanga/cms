<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useMaintenance } from '@/composables/maintenance/useMaintenance';
import { icons } from '@/lib/icons';
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

const tabProps = (tabValue: string) => {
    if (tabValue === 'staff') {
        return { staffImportResult: props.staffImportResult };
    }

    if (tabValue === 'students') {
        return { exportCounts: props.exportCounts };
    }

    return {};
};

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.maintenance' }];

const { maintenanceTabs } = useMaintenance();
const { activeTab } = storeToRefs(useMaintenanceStore());

const visibleTabs = computed(() => maintenanceTabs().filter((tab) => tab.show));
</script>

<template>
    <Head :title="$t('trans.maintenance')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in visibleTabs"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="text-sm font-light uppercase"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>
            </TabsList>
            <TabsContent
                v-for="tab in visibleTabs"
                :key="'content_' + tab.value"
                :value="tab.value"
                class="py-4"
            >
                <component :is="tab.component" v-bind="tabProps(tab.value)" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
