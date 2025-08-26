<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useSubjects } from '@/composables/institution/useSubjects';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Subject, SubjectParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const subject = ref<Subject>();
const form = useForm<SubjectParams>({
    name: '',
    description: '',
});

const { saveSubject } = useSubjects();

const { modals } = useModalStore();

watch(modals!, () => {
    subject.value = getModalEdit(APP_MODULE_KEYS.subjects);
    form.name = subject.value?.attributes?.name ?? '';
    form.description = subject.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.subjects"
        :title="`${subject ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.subject', 1)}`"
        :on-form-action="() => saveSubject(form, subject)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
