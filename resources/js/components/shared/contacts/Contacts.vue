<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useContacts } from '@/composables/shared/useContacts';
import { Contact } from '@/types/shared';
import { hasAbility } from '@/lib/permissions';

const { createContactColumns, onOpenModal } = useContacts();

interface Props {
    contacts: Contact[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create.contacts', 'update.contacts', 'manageOwnStudentContactDetails:students']);
</script>

<template>
    <DataTable
        :data="contacts"
        :columns="createContactColumns()"
        :on-create="() => onOpenModal()"
        :disable-create="!ability"
        :show-archived-filter="false"
    >
        <template #head-left v-if="title">
            <HeadingSmall :title="title" />
        </template>
    </DataTable>
</template>
