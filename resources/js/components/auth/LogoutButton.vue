<script setup lang="ts">
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { useAuth } from '@/composables/auth/useAuth';
import { router } from '@inertiajs/vue3';

const { logout } = useAuth();

const handleLogout = () => {
    logout();
    router.post(route('logout'));
};
</script>

<template>
    <BaseTooltip :content="`${$t('trans.logout')}`">
        <TextLink @click.prevent="handleLogout" href="" method="post" as="button" classes="text-destructive flex items-center">
            <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" />
        </TextLink>
    </BaseTooltip>
</template>
