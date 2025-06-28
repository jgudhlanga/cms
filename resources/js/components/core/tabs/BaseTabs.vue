<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { CustomTab } from '@/types/utils';

interface Props {
    tabs: Array<CustomTab>;
    defaultValue: string;
}

defineProps<Props>();
</script>

<template>
    <Tabs :default-value="defaultValue">
        <TabsList class="w-full">
            <TabsTrigger v-for="tab in tabs" :key="'tab_' + tab.value" :value="tab.value" class="text-xs font-light uppercase">
                {{ tab?.transLabel!() }}
            </TabsTrigger>
        </TabsList>
        <TabsContent v-for="tab in tabs" :value="tab.value" :key="'content_' + tab.value" class="py-4">
            <component :is="tab.component" />
        </TabsContent>
    </Tabs>
</template>
