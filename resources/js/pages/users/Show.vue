<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useShowUser } from '@/composables/users/useShowUser';
import { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
    auth: AuthObject;
    errors: object;
}>();

const { user } = props;
const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'user',
        href: route('users.index'),
    },
    { title: user?.attributes?.name },
];
const { userProfileTabs } = useShowUser();

const defaultTab = 'info';
const tabs = userProfileTabs(user);
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <BaseTabs :default-value="defaultTab" :tabs="tabs" />
    </PageContainer>
</template>
