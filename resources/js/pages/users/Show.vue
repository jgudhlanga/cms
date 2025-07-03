<script setup lang="ts">
import BaseImage from '@/components/core/image/BaseImage.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
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
        <div class="bg-endless h-[150px]"></div>
        <div class="z-10 -mt-[80px] flex flex-col items-center justify-center">
            <BaseImage :src="''" :is-person="true" classes="w-[130px] h-[130px] rounded-full border-2 border-primary shadow-lg" />
            <h1 class="mt-2 text-xl font-bold">{{ user?.attributes?.name }}</h1>
            <p class="text-muted-foreground pb-2">{{ user?.attributes?.email }}</p>
        </div>
        <BaseTabs :default-value="defaultTab" :tabs="tabs" />
    </PageContainer>
</template>
