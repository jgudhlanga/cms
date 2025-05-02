<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const { props } = usePage<PageProps>();
const { user } = props?.auth;
const { isMobile, state } = useSidebar();
const { getInitials } = useInitials();
const { defaultAvatarImage } = useDefaults();
</script>
<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <Avatar class="rounded-lg" :class="state === 'collapsed' ? 'size-6' : 'size-8'">
                            <AvatarImage :src="user.attributes.avatar ?? defaultAvatarImage" :alt="user.attributes.name" />
                            <AvatarFallback class="rounded-lg" :class="state === 'collapsed' ? 'size-6' : 'size-8'">
                                {{ getInitials(user.attributes.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">{{ user.attributes.name }}</span>
                            <span class="truncate text-xs">{{ user.attributes.email }}</span>
                        </div>
                        <component :is="icons[IconName.chevrons_up_down]" class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : 'right'"
                    align="end"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="p-0 font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                            <Avatar class="h-8 w-8 rounded-lg">
                                <AvatarImage :src="user.attributes.avatar ?? defaultAvatarImage" :alt="user.attributes.name" />
                                <AvatarFallback class="rounded-lg"> {{ getInitials(user.attributes.name) }} </AvatarFallback>
                            </Avatar>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">{{ user.attributes.name }}</span>
                                <span class="truncate text-xs">{{ user.attributes.email }}</span>
                            </div>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem>
                        <component :is="icons[IconName.user]" />
                        <Link class="" href="#" as="button">{{ $tChoice('trans.account', 1) }}</Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem>
                        <component :is="icons[IconName.logout]" />
                        <Link class="" method="post" :href="route('logout')" as="button">{{ $t('trans.logout') }}</Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
