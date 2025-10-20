<script setup lang="ts">
import LogoutButton from '@/components/auth/LogoutButton.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import Breadcrumbs from '@/components/core/util/Breadcrumbs.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useDefaults } from '@/composables/core/useDefaults';
import { useInitials } from '@/composables/core/useInitials';
import { PageProps } from '@/types';
import { BreadcrumbItemInterface } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import RemoveImpersonationButton from '@/components/auth/RemoveImpersonationButton.vue';
import { useUtils } from '@/composables/core/useUtils';

defineProps<{
    breadcrumbs?: BreadcrumbItemInterface[];
}>();
const page = usePage<PageProps>();
const { getInitials, } = useInitials();
const { defaultAvatarImage } = useDefaults();
const {isItTrue} = useUtils()
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
            <RemoveImpersonationButton v-if="isItTrue(page.props.auth.impersonating)"/>
            <BaseTooltip :content="`${$t('trans.user_account')}`">
                <TextLink :href="route('users.show', page.props.auth.user.id.toString())" method="get" as="button" classes="flex items-center">
                    <Avatar class="size-7 rounded-full">
                        <AvatarImage
                            :src="page.props.auth.user.attributes.avatarUrl ?? defaultAvatarImage"
                            :alt="page.props.auth.user.attributes.name"
                        />
                        <AvatarFallback class="size-7 rounded-full">
                            {{ getInitials(page.props.auth.user.attributes.name) }}
                        </AvatarFallback>
                    </Avatar>
                </TextLink>
            </BaseTooltip>
            <LogoutButton />
        </div>
    </header>
    <div class="flex h-full w-full flex-col pb-10">
        <slot />
    </div>
</template>
