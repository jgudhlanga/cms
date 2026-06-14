<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useShowEnrolment } from '@/composables/students/useShowEnrolment';
import { useStudentTabsStore } from '@/store/portal/useStudentTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { Student } from '@/types/students';
import { Link } from '@/types/ui';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import SponsorForm from '@/components/students/sponsors/SponsorForm.vue';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import NextOfKinForm from '@/components/shared/nextOfKin/NextOfKinForm.vue';
import EditBasicInfo from '@/components/shared/basicInfo/EditBasicInfo.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';

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

const visibleTabs = computed(() => enrolmentTabs(String(student.id)));
const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user?.attributes?.name" :line-two="user?.attributes?.email" />
        <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
        <div class="py-4">
            <component :is="activeSection?.component" v-if="activeSection" />
        </div>
        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
        <NextOfKinForm :post-url="route('portal.next-of-kins.store')" />
        <SponsorForm />
        <EditBasicInfo />
    </PageContainer>
</template>
