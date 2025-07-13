<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import NextOfKinForm from '@/components/shared/nextOfKin/NextOfKinForm.vue';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
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
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'personal_details' }];
const { studentTabs } = useStudentPortal();
const { activeTab } = storeToRefs(useStudentTabsStore());
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user.attributes?.name" :line-two="user.attributes?.email" />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger v-for="tab in studentTabs()" :key="'tab_' + tab.value" :value="tab.value" class="text-xs font-light uppercase">
                    {{ tab?.transLabel!() }}
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in studentTabs()" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
        <SponsorForm />
        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
        <NextOfKinForm :post-url="route('portal.next-of-kins.store')" />
    </PageContainer>
</template>
