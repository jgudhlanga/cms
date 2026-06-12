<script setup lang="ts">
import ConfirmDialog from '@/components/core/modal/ConfirmDialog.vue';
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import AppSidebar from '@/components/core/sidebar/AppSidebar.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { useFlashAlerts } from '@/composables/core/useFlashAlerts';
import { useUserPreference } from '@/composables/core/useUserPreference';
import { usePreferencesStore } from '@/store/core/preferences.store';
import { BreadcrumbItemType } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { ModalsContainer } from 'vue-final-modal';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage();
useFlashAlerts();
const preferencesStore = usePreferencesStore();
const { hydratePreferenceOnce } = useUserPreference();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const updateSidebarState = (open: boolean): void => {
    preferencesStore.setSideBarState(open);
};

onMounted(async () => {
    if (!isAuthenticated.value) {
        preferencesStore.markHydrated();

        return;
    }

    await hydratePreferenceOnce();
});
</script>
<template>
    <SidebarProvider :open="preferencesStore.sideBarState" @update:open="updateSidebarState">
        <AppSidebar />
        <SidebarInset class="min-w-0 overflow-x-clip">
            <div class="flex h-full min-w-0 w-full max-w-full flex-1 flex-col gap-3 overflow-x-clip rounded-xl px-3 sm:gap-4 sm:px-5 lg:px-8">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
    <ConfirmDialog />
    <ErrorDialog />
    <ModalsContainer />
</template>
