<script setup lang="ts">
import MaintenancePageHeader from '@/pages/maintenance/partials/MaintenancePageHeader.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import { cn } from '@/lib/utils';
import type { MaintenanceExportCounts } from '@/types/maintenance-exports';
import type { StaffImportResult } from '@/types/staff-import';
import type { CustomTab } from '@/types/utils';
import { computed } from 'vue';

const props = defineProps<{
    tabs: CustomTab[];
    activeTab: string;
    staffImportResult?: StaffImportResult | null;
    exportCounts?: MaintenanceExportCounts;
}>();

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const activeTabModel = computed({
    get: () => props.activeTab,
    set: (value: string) => emit('update:activeTab', value),
});

const activeSection = computed(() => props.tabs.find((tab) => tab.value === props.activeTab));

const sectionDescription = computed(() => activeSection.value?.transDescription?.() ?? '');

const isFullBleedSection = computed(() => props.activeTab === 'users' || props.activeTab === 'staff' || props.activeTab === 'archives');

const contentWrapperClass = computed(() =>
    cn(
        'min-w-0 w-full',
        isFullBleedSection.value ? '' : 'rounded-lg border border-border bg-card p-4',
    ),
);
 
const tabProps = (tabValue: string) => {
    if (tabValue === 'staff') {
        return { staffImportResult: props.staffImportResult };
    }

    if (tabValue === 'students') {
        return { exportCounts: props.exportCounts };
    }

    return {};
};
</script>

<template>
    <div class="w-full min-w-0 space-y-4 px-2 sm:px-4">
        <MaintenancePageHeader :section-description="sectionDescription" />

        <BaseSectionNav
            v-model:active-tab="activeTabModel"
            :tabs="tabs"
            :aria-label="$t('trans.maintenance')"
        />

        <div :class="contentWrapperClass">
            <component
                :is="activeSection?.component"
                v-if="activeSection"
                v-bind="tabProps(activeSection.value)"
            />
        </div>
    </div>
</template>
