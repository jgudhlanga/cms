<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import Breadcrumbs from '@/components/core/util/Breadcrumbs.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAuth } from '@/composables/auth/useAuth';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { PageProps } from '@/types';
import { BreadcrumbItemInterface } from '@/types/ui';
import { router, usePage } from '@inertiajs/vue3';

defineProps<{
    breadcrumbs?: BreadcrumbItemInterface[];
}>();
const { props } = usePage<PageProps>();
const { user } = props?.auth;
const { getInitials } = useInitials();
const { defaultAvatarImage } = useDefaults();

const { logout } = useAuth();

const handleLogout = () => {
    logout();
    router.post(route('logout'));
};
</script>
<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
    >
        <div class="flex items-center gap-2 px-4">
            <SidebarTrigger class="text-primary -ml-4 font-bold" />
            <Separator orientation="vertical" class="mr-2 h-4" />
            <Breadcrumbs :breadcrumbs="breadcrumbs ?? []" />
        </div>
        <div class="flex items-center justify-center space-x-4">
            <BaseTooltip :content="`${$t('trans.user_account')}`">
                <TextLink :href="route('users.show', user.id.toString())" method="get" as="button" classes="flex items-center">
                    <Avatar class="size-9 rounded-full">
                        <AvatarImage :src="user.attributes.avatar ?? defaultAvatarImage" :alt="user.attributes.name" />
                        <AvatarFallback class="size-9 rounded-full">
                            {{ getInitials(user.attributes.name) }}
                        </AvatarFallback>
                    </Avatar>
                </TextLink>
            </BaseTooltip>
            <BaseTooltip :content="`${$t('trans.logout')}`">
                <TextLink @click.prevent="handleLogout" href="" method="post" as="button" classes="text-destructive flex items-center">
                    <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" />
                </TextLink>
            </BaseTooltip>
        </div>
    </header>
    <div class="flex h-full w-full flex-col pb-10">
        <slot />
    </div>
</template>
