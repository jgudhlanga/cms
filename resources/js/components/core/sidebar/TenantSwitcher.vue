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
const { isMobile, state, toggleSidebar } = useSidebar();
</script>
<template>
    <div class="relative p-3">
        <div v-if="isMobile || state === 'collapsed'" class="flex flex-col items-center gap-2 text-center">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square items-center justify-center rounded-full"
                :class="isMobile ? 'size-12' : 'size-10'"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
            </div>
            <div v-if="isMobile" class="grid justify-items-center text-sm leading-tight">
                <span class="truncate font-semibold uppercase text-sidebar-foreground">
                    {{ activeTenant.attributes.name }}
                </span>
                <span class="truncate text-xs text-sidebar-foreground/80">{{ activeTenant.attributes.bio }}</span>
            </div>
        </div>
        <div v-else class="flex items-center space-x-2">
            <div
                class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square items-center justify-center rounded-full"
                :class="state === 'collapsed' ? 'size-6' : 'size-8'"
            >
                <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
            </div>
            <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold uppercase text-sidebar-foreground">
                    {{ activeTenant.attributes.name }}
                </span>
                <span class="truncate text-xs text-sidebar-foreground/80">{{ activeTenant.attributes.bio }}</span>
            </div>
        </div>
        <IconButton
            :icon="IconName.close"
            :variant="ColorVariant.danger_outline"
            @click.stop.prevent="toggleSidebar"
            class="absolute top-3 right-3 flex md:hidden"
            aria-label="Close Sidebar"
        />
    </div>
</template>
