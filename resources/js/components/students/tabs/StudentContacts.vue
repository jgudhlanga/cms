<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Contacts from '@/components/shared/contacts/Contacts.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Contact } from '@/types/shared';
import { onMounted, ref } from 'vue';

interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.contacts'),
});

const { isLoading, getStudentData } = useStudentPortal();
const contacts = ref<Contact[]>([]);
onMounted(async () => {
    contacts.value = await getStudentData(props.url);
});
</script>

<template>
    <DataLoadingSpinner v-if="isLoading" />
    <Contacts
        v-else
        :contacts="contacts"
        :title="`${$t('trans.my')}
        ${$tChoice('trans.contact', 2)}`"
    />
</template>
