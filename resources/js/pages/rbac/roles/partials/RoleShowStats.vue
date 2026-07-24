<script setup lang="ts">
import StatsCard from '@/pages/rbac/roles/partials/StatsCard.vue';
import { Role } from '@/types/rbac';

defineProps<{ role?: Role }>();
</script>

<template>
    <div class="flex flex-col space-y-3">
        <div class="flex space-x-3">
            <StatsCard :title="$tChoice('trans.user', 2)" :count="role?.attributes?.usersCount ?? 0" />
            <StatsCard :title="`${$t('trans.assigned')} ${$tChoice('trans.permission', 2)}`" :count="role?.attributes?.permissionsCount ?? 0" />
        </div>
    </div>
</template>
