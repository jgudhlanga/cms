<script setup lang="ts">
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useRelationships } from '@/composables/shared/useRelationships';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Relationship, RelationshipParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const relationship = ref<Relationship>();
const form = useForm<RelationshipParams>({
    name: '',
});

const { saveRelationship } = useRelationships();

const { modals } = useModalStore();

watch(modals!, () => {
    relationship.value = getModalEdit(APP_MODULE_KEYS.relationships);
    form.name = relationship.value?.attributes?.name ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.relationships"
        :title="`${relationship ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.relationship', 1)}`"
        :on-form-action="() => saveRelationship(form, relationship)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
        </template>
    </BaseModal>
</template>
