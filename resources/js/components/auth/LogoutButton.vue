<script setup lang="ts">
import { useAuth } from '@/composables/auth/useAuth';
import { IconName } from '@/enums/icons';
import { router } from '@inertiajs/vue3';

const { logout } = useAuth();

const handleLogout = () => {
    logout();
    router.post(route('logout'));
};
</script>

<template>
    <BaseTooltip :content="`${$t('trans.logout')}`">
        <TextLink @click.prevent="handleLogout" href="" method="post" as="button" classes="flex items-center">
            <IconButton :icon="IconName.logout" tone="header-danger" />
        </TextLink>
    </BaseTooltip>
</template>
