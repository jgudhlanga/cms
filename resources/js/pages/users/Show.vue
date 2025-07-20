<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useShowUser } from '@/composables/users/useShowUser';
import { icons } from '@/lib/icons';
import { useUserTabsStore } from '@/store/users/useUserTabsStore';
import { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

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

const { activeTab } = storeToRefs(useUserTabsStore());
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in userProfileTabs(user)"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="text-xs font-light uppercase flex items-center"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in userProfileTabs(user)" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
