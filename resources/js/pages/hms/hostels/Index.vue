<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useHms } from '@/composables/hms/useHms';
import { icons } from '@/lib/icons';
import CreateEditHostel from '@/pages/hms/components/forms/CreateEditHostel.vue';
import CreateEditRoom from '@/pages/hms/components/forms/CreateEditRoom.vue';
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
</script>

<template>
    <Head :title="$tChoice('hms.title', 2)" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger v-for="tab in visibleTabs" :key="'tab_' + tab.value" :value="tab.value" class="text-sm font-light uppercase">
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in visibleTabs" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
        <!-- ── Create / Edit modal ───────────────────────────────────────── -->
        <CreateEditHostel :wardens="wardens" />
        <CreateEditRoom />
    </PageContainer>
</template>