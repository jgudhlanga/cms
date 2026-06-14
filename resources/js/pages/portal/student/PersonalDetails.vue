<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';
import EditBasicInfo from '@/components/shared/basicInfo/EditBasicInfo.vue';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import NextOfKinForm from '@/components/shared/nextOfKin/NextOfKinForm.vue';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { icons } from '@/lib/icons';
import { useStudentTabsStore } from '@/store/portal/useStudentTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

interface Props {
    student: Student;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { transKey: 'personal_details' }];
const { studentTabs } = useStudentPortal();
const { activeTab } = storeToRefs(useStudentTabsStore());

const visibleTabs = computed(() => studentTabs());
const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));

const getTextClasses = (tab: string) => {
    if (tab === activeTab.value) {
        return 'text-primary';
    }
    return 'text-accent-foreground';
};
const setActiveTab = (tab: string) => {
    activeTab.value = tab;
};
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <div class="mt-2 hidden md:block">
            <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
            <div class="py-4">
                <component :is="activeSection?.component" v-if="activeSection" />
            </div>
        </div>
        <div class="my-6 flex flex-col md:hidden">
            <div v-for="tab in visibleTabs" :key="'mobile_content_' + tab.value" class="mb-6" v-show="activeTab === tab.value">
                <h2 class="mb-6 text-lg font-semibold uppercase">{{ tab?.transLabel!() }}</h2>
                <component :is="tab.component" />
            </div>
        </div>
        <nav class="fixed right-0 bottom-0 left-0 z-50 border-t border-border bg-card px-4 py-2 shadow-lg md:hidden">
            <div class="flex items-center justify-between">
                <button
                    v-for="item in visibleTabs"
                    :key="item.value"
                    @click="setActiveTab(item.value)"
                    class="nav-item flex w-16 flex-col items-center justify-center py-1 transition-all duration-200"
                    :class="getTextClasses(item.value)"
                >
                    <component :is="icons[item?.icon!]" />
                    <span class="mt-1 text-xs font-medium transition-all duration-200" :class="getTextClasses(item.value)">
                        {{ item?.transLabel!() }}
                    </span>
                </button>
            </div>
        </nav>
        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
        <NextOfKinForm :post-url="route('portal.next-of-kins.store')" />
        <SponsorForm />
        <EditBasicInfo />
    </PageContainer>
</template>
