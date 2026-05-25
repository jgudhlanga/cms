<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useStudentEnrolmentStatuses } from '@/composables/students/useStudentEnrolmentStatuses';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { StudentEnrolmentStatus, StudentEnrolmentStatusParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const studentEnrolmentStatus = ref<StudentEnrolmentStatus>();
const form = useForm<StudentEnrolmentStatusParams>({
    name: '',
    description: '',
    color: '',
});

const { save } = useStudentEnrolmentStatuses();
const { modals } = useModalStore();

watch(modals!, () => {
    studentEnrolmentStatus.value = getModalEdit(APP_MODULE_KEYS.student_enrolment_statuses);
    form.name = studentEnrolmentStatus.value?.attributes?.name ?? '';
    form.description = studentEnrolmentStatus.value?.attributes?.description ?? '';
    form.color = studentEnrolmentStatus.value?.attributes?.color ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_enrolment_statuses"
        :title="`${studentEnrolmentStatus ? $t('trans.create') : $t('trans.create')} ${$tChoice('students.enrolment_status', 1)}`"
        :on-form-action="() => save(form, studentEnrolmentStatus)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
