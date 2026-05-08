<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';
import EditBasicInfo from '@/components/shared/basicInfo/EditBasicInfo.vue';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import NextOfKinForm from '@/components/shared/nextOfKin/NextOfKinForm.vue';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useShowEnrolment } from '@/composables/students/useShowEnrolment';
import { icons } from '@/lib/icons';
import { useStudentTabsStore } from '@/store/portal/useStudentTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';
import { storeToRefs } from 'pinia';

interface Props {
    student: Student;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student } = props;
const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: 'Enrolment lookup', href: route('enrolments.enrolment-lookup') },
    { title: student.relationships?.user?.attributes?.name ?? 'Profile' },
];

const user = student.relationships?.user;
const { enrolmentTabs } = useShowEnrolment();

const { activeTab } = storeToRefs(useStudentTabsStore());
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user?.attributes?.name" :line-two="user?.attributes?.email" />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in enrolmentTabs(String(student.id))"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="flex items-center text-xs font-light uppercase"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in enrolmentTabs(String(student.id))" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
        <NextOfKinForm :post-url="route('portal.next-of-kins.store')" />
        <SponsorForm />
        <EditBasicInfo />
    </PageContainer>
</template>
