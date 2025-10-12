<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import CustomCard from '@/components/core/card/CustomCard.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import Empty from '@/components/core/util/Empty.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import TabsAddNewButton from '@/components/students/tabs/TabsAddNewButton.vue';
import { useContacts } from '@/composables/shared/useContacts';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { Contact } from '@/types/shared';

const { createContactColumns, onOpenModal, deleteContact } = useContacts();

interface Props {
    contacts: Contact[];
    title?: string;
}

defineProps<Props>();
const ability = hasAbility(['create:contacts', 'update:contacts', 'manageOwnStudentContactDetails:students']);
</script>

<template>
    <div class="hidden flex-col md:flex">
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
    </div>
    <div class="flex flex-col space-y-6 md:hidden">
        <TabsAddNewButton :action="() => onOpenModal()" />
        <template v-if="contacts && contacts.length > 0">
            <CustomCard :title="String(index + 1)" v-for="(contact, index) in contacts" :key="contact.id">
                <template #header-buttons>
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="onOpenModal(contact)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="deleteContact(String(contact.id))" />
                </template>
                <template #body>
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <GridLabelValue :label="$tChoice('trans.name', 1)" :value="contact?.attributes?.name ?? ''" />
                        <GridLabelValue :label="$t('trans.phone_number')" :value="contact?.attributes?.phoneNumber ?? ''" />
                        <GridLabelValue :label="$t('trans.alt_phone_number')" :value="contact?.attributes?.altPhoneNumber ?? ''" />
                        <GridLabelValue :label="$t('trans.email_address')" :value="contact?.attributes?.emailAddress ?? ''" />
                        <GridLabelValue :label="$t('trans.alt_email_address')" :value="contact?.attributes?.altEmailAddress ?? ''" />
                    </div>
                </template>
            </CustomCard>
        </template>
        <Empty v-else />
    </div>
</template>
