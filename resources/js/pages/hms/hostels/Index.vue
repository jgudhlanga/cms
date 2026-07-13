<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import { useHms } from '@/composables/hms/useHms';
import CreateEditHostel from '@/pages/hms/components/forms/CreateEditHostel.vue';
import CreateEditHostelAmenity from '@/pages/hms/components/forms/CreateEditHostelAmenity.vue';
import CreateEditRoom from '@/pages/hms/components/forms/CreateEditRoom.vue';
import CreateEditApplication from '@/pages/hms/components/forms/CreateEditApplication.vue';
import ReassignRoomDialog from '@/pages/hms/components/forms/ReassignRoomDialog.vue';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';


interface Props {
    wardens: Array<{ id: number | string; name: string | null }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'hms.title' }];

const { hmsTabs } = useHms();
const { activeTab } = storeToRefs(useHmsStore());

const visibleTabs = computed(() => {
    return hmsTabs().filter((tab) => tab.show);
});

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));
</script>

<template>
    <Head :title="$tChoice('hms.title', 2)" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
        <div class="py-4">
            <component :is="activeSection?.component" v-if="activeSection" />
        </div>
        <!-- ── Create / Edit modal ───────────────────────────────────────── -->
        <CreateEditHostel :wardens="wardens" />
        <CreateEditHostelAmenity />
        <CreateEditRoom />
        <CreateEditApplication />
        <ReassignRoomDialog />
    </PageContainer>
</template>