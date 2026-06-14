<script setup lang="ts">
import Authentication from '@/components/users/Authentication.vue';
import ProfileFieldCard from '@/components/users/profile/ProfileFieldCard.vue';
import { useUtils } from '@/composables/core/useUtils';
import type { User } from '@/types/users';
import { computed } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { formatDate } = useUtils();

const lastLogin = computed(() => {
    const value = props.user.attributes.lastLoginAt;

    return value ? formatDate(value, 'LL') : '';
});

const loginCount = computed(() => String(props.user.attributes.loginCount ?? 0));
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-3">
            <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                {{ $t('trans.account_activity') }}
            </h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ProfileFieldCard
                    :label="$t('trans.last_login')"
                    :value="lastLogin"
                    :is-empty="!lastLogin"
                    :empty-label="$t('trans.not_provided')"
                />
                <ProfileFieldCard
                    :label="$t('trans.login_count')"
                    :value="loginCount"
                />
            </div>
        </section>

        <Authentication :user="user" hide-authorization />
    </div>
</template>
