<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import UserProfileHeader from '@/components/users/UserProfileHeader.vue';
import UserProfileImpersonateSection from '@/components/users/profile/UserProfileImpersonateSection.vue';
import { useSectionTabQuerySync } from '@/composables/core/useSectionTabQuerySync';
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

useSectionTabQuerySync(activeTab, () => visibleTabs.value.map((tab) => tab.value));
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="w-full min-w-0 max-w-full overflow-x-clip rounded-xl border border-border bg-card text-card-foreground shadow-sm">
            <UserProfileHeader :user="user" />

            <div class="w-full min-w-0 space-y-4 px-3 pb-4 md:px-4">
                <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" :grouped="false" nav-id="user-tabs" />

                <div
                    :id="`user-tabs-panel-${activeTab}`"
                    role="tabpanel"
                    :aria-labelledby="`user-tabs-tab-${activeTab}`"
                    tabindex="0"
                    class="min-w-0 pt-1"
                >
                    <component :is="activeSection?.component" v-if="activeSection" />
                </div>
            </div>

            <UserProfileImpersonateSection :user="user" />
        </div>
    </PageContainer>
</template>
