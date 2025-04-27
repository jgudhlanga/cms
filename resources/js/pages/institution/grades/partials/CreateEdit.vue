<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Grade, GradeParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const grade = ref<Grade>();
const form = useForm<GradeParams>({
    name: '',
    description: '',
});

const { saveGrade } = useGrades();

const { modals } = useModalStore();

watch(modals!, () => {
    grade.value = getModalEdit(APP_MODULE_KEYS.grades);
    form.name = grade.value?.attributes?.name ?? '';
    form.description = grade.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.grades"
        :title="`${grade ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.grade', 1)}`"
        :on-form-action="() => saveGrade(form, grade)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
