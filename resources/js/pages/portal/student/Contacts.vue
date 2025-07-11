<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import Addresses from '@/components/shared/address/Addresses.vue';
import Contacts from '@/components/shared/contacts/Contacts.vue';
import { AuthObject } from '@/types/data-pagination';
import { Address, Contact } from '@/types/shared';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import ContactsForm from '@/components/shared/contacts/ContactsForm.vue';
import AddressesForm from '@/components/shared/address/AddressesForm.vue';

interface Props {
    contacts: Contact[];
    addresses: Address[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'contact' }];
</script>
<template>
    <Head :title="$tChoice('trans.contact', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
       <div class="flex flex-col space-y-5">
           <Contacts :contacts="contacts" :title="`${$t('trans.my')} ${$tChoice('trans.contact', 2)}`" />
           <Addresses :addresses="addresses" :title="`${$t('trans.my')} ${$tChoice('trans.address', 2)}`" />
       </div>
        <!--MODALS -->
        <ContactsForm :post-url="route('portal.contacts.store')" />
        <AddressesForm :post-url="route('portal.address.store')" />
    </PageContainer>
</template>
