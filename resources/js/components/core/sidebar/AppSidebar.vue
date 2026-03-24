<script setup lang="ts">
import NavMain from '@/components/core/sidebar/NavMain.vue';
import NavUser from '@/components/core/sidebar/NavUser.vue';
import TenantSwitcher from '@/components/core/sidebar/TenantSwitcher.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail } from '@/components/ui/sidebar';
import { HTMLAttributes } from 'vue';

type SidebarProps = {
    side?: 'left' | 'right';
    variant?: 'sidebar' | 'floating' | 'inset';
    collapsible?: 'offcanvas' | 'icon' | 'none';
    class?: HTMLAttributes['class'];
};
const props = withDefaults(defineProps<SidebarProps>(), {
    collapsible: 'icon',
});
</script>
<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <TenantSwitcher />
        </SidebarHeader>
        <SidebarContent class="mt-5">
            <NavMain />
        </SidebarContent>
        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
</template>
