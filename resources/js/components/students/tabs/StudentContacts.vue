<script setup lang="ts">
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { onMounted, ref } from 'vue';
import { Contact } from '@/types/shared';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Contacts from '@/components/shared/contacts/Contacts.vue';

const { isLoading, getStudentData } = useStudentPortal();
const contacts = ref<Contact[]>([]);
onMounted(async () => {
   contacts.value = await getStudentData(route('v1.portal.contacts'));
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <Contacts v-else :contacts="contacts" :title="`${$t('trans.my')} ${$tChoice('trans.contact', 2)}`" />
</template>
