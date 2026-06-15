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
import { useCloseMobileSidebar } from '@/composables/core/useCloseMobileSidebar';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<PageProps>();
const { isMobile, state } = useSidebar();
const { getInitials } = useInitials();
const { defaultAvatarImage } = useDefaults();
const closeMobileSidebar = useCloseMobileSidebar();
</script>
<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <Avatar class="rounded-lg" :class="state === 'collapsed' ? 'size-6' : 'size-7'">
                            <AvatarImage
                                :src="page.props.auth?.user.attributes.avatarUrl ?? defaultAvatarImage"
                                :alt="page.props.auth?.user.attributes.name"
                            />
                            <AvatarFallback class="rounded-lg" :class="state === 'collapsed' ? 'size-6' : 'size-7'">
                                {{ getInitials(page.props.auth.user.attributes.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="grid flex-1 text-left text-sm leading-none">
                            <span class="truncate font-semibold">{{ page.props.auth.user.attributes.name }}</span>
                            <span class="truncate text-xs">{{ page.props.auth.user.attributes.email }}</span>
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
                                <AvatarImage
                                    :src="page.props.auth.user.attributes.avatarUrl ?? defaultAvatarImage"
                                    :alt="page.props.auth.user.attributes.name"
                                />
                                <AvatarFallback class="rounded-lg"> {{ getInitials(page.props.auth.user.attributes.name) }} </AvatarFallback>
                            </Avatar>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">{{ page.props.auth.user.attributes.name }}</span>
                                <span class="truncate text-xs">{{ page.props.auth.user.attributes.email }}</span>
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
                        <Link class="" method="post" :href="route('logout')" as="button" @click="closeMobileSidebar">{{ $t('trans.logout') }}</Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
