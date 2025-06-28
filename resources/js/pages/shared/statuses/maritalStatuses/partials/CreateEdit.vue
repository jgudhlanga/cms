<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useMaritalStatuses } from '@/composables/shared/useMaritalStatuses';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { MaritalStatus, MaritalStatusParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const maritalStatus = ref<MaritalStatus>();
const form = useForm<MaritalStatusParams>({
    title: '',
});

const { saveMaritalStatus } = useMaritalStatuses();

const { modals } = useModalStore();

watch(modals!, () => {
    maritalStatus.value = getModalEdit(APP_MODULE_KEYS.marital_statuses);
    form.title = maritalStatus.value?.attributes?.title ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.marital_statuses"
        :title="`${maritalStatus ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.marital_status', 1)}`"
        :on-form-action="() => saveMaritalStatus(form, maritalStatus)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
        </template>
    </BaseModal>
</template>
