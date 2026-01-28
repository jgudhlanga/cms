<script setup lang="ts">
import ConfirmDialog from '@/components/core/modal/ConfirmDialog.vue';
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import AppSidebar from '@/components/core/sidebar/AppSidebar.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { BreadcrumbItemType } from '@/types/ui';
import { ModalsContainer } from 'vue-final-modal';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();
</script>
<template>
    <SidebarProvider>
        <AppSidebar />
        <SidebarInset>
            <div class="flex h-full flex-1 flex-col gap-4 rounded-xl px-8">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
    <ConfirmDialog />
    <ErrorDialog />
    <ModalsContainer />
</template>
