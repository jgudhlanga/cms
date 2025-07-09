<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useIdTypes } from '@/composables/shared/useIdTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { IdType, IdTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const idType = ref<IdType>();
const form = useForm<IdTypeParams>({
    name: '',
    description: '',
});

const { saveIdType } = useIdTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    idType.value = getModalEdit(APP_MODULE_KEYS.id_types);
    form.name = idType.value?.attributes?.name ?? '';
    form.description = idType.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.id_types"
        :title="`${idType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.id_type', 1)}`"
        :on-form-action="() => saveIdType(form, idType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
