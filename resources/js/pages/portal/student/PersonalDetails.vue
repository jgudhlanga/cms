<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';
import EditBasicInfo from '@/components/shared/basicInfo/EditBasicInfo.vue';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import NextOfKinForm from '@/components/shared/nextOfKin/NextOfKinForm.vue';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { icons } from '@/lib/icons';
import { useStudentTabsStore } from '@/store/portal/useStudentTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

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
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <div class="flex flex-col w-svw">
            <Tabs :default-value="activeTab" v-model="activeTab">
                <TabsList class="">
                    <TabsTrigger
                        v-for="tab in studentTabs()"
                        :key="'tab_' + tab.value"
                        :value="tab.value"
                        class="flex items-center text-xs font-light uppercase"
                    >
                        <component :is="icons[tab?.icon!]" />
                        <span>{{ tab?.transLabel!() }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in studentTabs()" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                    <component :is="tab.component" />
                </TabsContent>
            </Tabs>
        </div>

        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
        <NextOfKinForm :post-url="route('portal.next-of-kins.store')" />
        <SponsorForm />
        <EditBasicInfo />
    </PageContainer>
</template>
