<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import Contacts from '@/components/shared/contacts/Contacts.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Contact } from '@/types/shared';
import { onMounted, ref } from 'vue';

const { isLoading, getStudentData } = useStudentPortal();
const contacts = ref<Contact[]>([]);
onMounted(async () => {
    contacts.value = await getStudentData(route('v1.portal.contacts'));
});
</script>

<template>
    <TableLoading v-if="isLoading" />
    <Contacts v-else :contacts="contacts" :title="`${$t('trans.my')} ${$tChoice('trans.contact', 2)}`" />
</template>
