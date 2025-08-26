<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useDocumentTypes } from '@/composables/shared/useDocumentTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { DocumentType, DocumentTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const documentType = ref<DocumentType>();
const form = useForm<DocumentTypeParams>({
    name: '',
    description: '',
});

const { saveDocumentType } = useDocumentTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    documentType.value = getModalEdit(APP_MODULE_KEYS.document_types);
    form.name = documentType.value?.attributes?.name ?? '';
    form.description = documentType.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.document_types"
        :title="`${documentType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.document_type', 1)}`"
        :on-form-action="() => saveDocumentType(form, documentType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
