<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Course } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

const props = defineProps<Props>();

const config = ref<Course>();
const form = useForm<any>({
    students_per_class: null,
});

const { modals } = useModalStore();

watch(modals!, () => {
    config.value = getModalEdit(APP_MODULE_KEYS.student_per_class);
    form.defaults();
});

const submitForm = () => {};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_per_class"
        :title="$tChoice('academic_calendar.class_unit_size', 1)"
        :on-form-action="() => submitForm()"
        :form="form"
    >
        <template #body>
            <BaseInput
                input-id="students_per_class"
                :inputAutoFocus="true"
                v-model="form.students_per_class"
                @input="clearFormErrors(form, 'students_per_class')"
                :error="form.errors.students_per_class"
            />
        </template>
    </BaseModal>
</template>
