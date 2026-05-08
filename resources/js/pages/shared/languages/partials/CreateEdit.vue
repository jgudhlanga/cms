<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useLanguages } from '@/composables/shared/useLanguages';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Language, LanguageParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const language = ref<Language>();
const form = useForm<LanguageParams>({
    title: '',
});

const { saveLanguage } = useLanguages();

const { modals } = useModalStore();

watch(modals!, () => {
    language.value = getModalEdit(APP_MODULE_KEYS.languages);
    form.title = language.value?.attributes?.title ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.languages"
        :title="`${language ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.language', 1)}`"
        :on-form-action="() => saveLanguage(form, language)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
        </template>
    </BaseModal>
</template>
