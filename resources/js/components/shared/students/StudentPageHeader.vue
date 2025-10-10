<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import AppLogo from '@/components/core/image/AppLogo.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import Heading from '@/components/core/util/Heading.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useAuth } from '@/composables/auth/useAuth';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage<PageProps>();
const { user } = page.props.auth;
const { logout } = useAuth();

const handleLogout = () => {
    logout();
    router.post(route('logout'));
};
</script>

<template>
    <nav class="fixed top-0 right-0 left-0 z-50 w-full bg-white px-10 shadow">
        <div class="flex w-full items-center justify-between space-x-5 py-3 md:mx-auto md:w-7/8">
            <div class="flex size-8 items-center justify-start rounded-full border">
                <AppLogo class="shrink-0 rounded-full" />
            </div>
            <Heading :title="user.attributes?.name" />
            <div class="flex">
                <BaseTooltip :content="`${$t('trans.logout')}`">
                    <TextLink @click.prevent="handleLogout" href="" method="post" as="button" classes="text-destructive flex items-center">
                        <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" />
                    </TextLink>
                </BaseTooltip>
            </div>
        </div>
    </nav>
</template>
P
