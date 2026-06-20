<script setup lang="ts">
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useReligions } from '@/composables/shared/useReligions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Religion, ReligionParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const religion = ref<Religion>();
const form = useForm<ReligionParams>({
    name: '',
    description: '',
});

const { saveReligion } = useReligions();

const { modals } = useModalStore();

watch(modals!, () => {
    religion.value = getModalEdit(APP_MODULE_KEYS.religions);
    form.name = religion.value?.attributes?.name ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.religions"
        :title="`${religion ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.religion', 1)}`"
        :on-form-action="() => saveReligion(form, religion)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
        </template>
    </BaseModal>
</template>
