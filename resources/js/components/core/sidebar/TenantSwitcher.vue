<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import { useSidebar } from '@/components/ui/sidebar';
import { useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { TenantInterface } from '@/types/tenants';
import { ref } from 'vue';

const { tenants } = useSidebarMenu();

const activeTenant = ref<TenantInterface>(tenants[0]);
const { state } = useSidebar();

const { toggleSidebar } = useSidebar();
</script>
<template>
    <div class="flex space-x-2 p-3">
        <div
            class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square items-center justify-center rounded-full"
            :class="state === 'collapsed' ? 'size-6' : 'size-8'"
        >
            <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-semibold uppercase text-white">
                {{ activeTenant.attributes.name }}
            </span>
            <span class="truncate text-xs text-sidebar-foreground">{{ activeTenant.attributes.bio }}</span>
        </div>
        <IconButton
            :icon="IconName.close"
            :variant="ColorVariant.danger_outline"
            @click.stop.prevent="toggleSidebar"
            class="flex md:hidden"
            aria-label="Close Sidebar"
        />
    </div>
</template>
