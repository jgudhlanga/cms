<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useSidebarMenu } from '@/composables/core/useSidebarMenu';
import { TenantInterface } from '@/types/tenants';
import { IconName, icons } from '@/lib/icons';
import { ref } from 'vue';

const { tenants } = useSidebarMenu();

const activeTenant = ref<TenantInterface>(tenants[0]);
const { isMobile, state } = useSidebar();
</script>
<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div
                            class="flex aspect-square items-center justify-center rounded-full bg-sidebar-primary text-sidebar-primary-foreground"
                            :class="state === 'collapsed' ? 'size-6' : 'size-8'"
                        >
                            <component :is="activeTenant.attributes.logo" class="rounded-full border-2 border-white" />
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                          <span class="truncate font-semibold uppercase">
                            {{ activeTenant.attributes.name }}
                          </span>
                            <span class="truncate text-xs">{{ activeTenant.attributes.bio }}</span>
                        </div>
                        <component :is="icons[IconName.chevrons_up_down]" class="ml-auto" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    align="start"
                    :side="isMobile ? 'bottom' : 'right'"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Tenants {{ state }}
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        class="gap-2 p-2"
                        @click="activeTenant = tenant"
                    >
                        <div
                            class="flex items-center justify-center rounded-sm border"
                            :class="state === 'collapsed' ? 'size-6' : 'size-8'"
                        >
                            <component :is="tenant.attributes.logo" class="shrink-0" />
                        </div>
                        {{ tenant.attributes.name }}
                        <DropdownMenuShortcut>
                            <component :is="icons[IconName.dots_vertical]" class="ml-auto" />
                        </DropdownMenuShortcut>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
