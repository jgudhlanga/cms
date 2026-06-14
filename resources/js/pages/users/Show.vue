<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useShowUser } from '@/composables/users/useShowUser';
import { useUserTabsStore } from '@/store/users/useUserTabsStore';
import { AuthObject } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

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

const visibleTabs = computed(() => userProfileTabs(user));
const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
        <div class="py-4">
            <component :is="activeSection?.component" v-if="activeSection" />
        </div>
    </PageContainer>
</template>
 