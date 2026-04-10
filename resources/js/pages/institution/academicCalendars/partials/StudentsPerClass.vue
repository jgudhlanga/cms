<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicClassConfigPayload } from '@/types/academic-calendar';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

const props = defineProps<Props>();

    const {storePerClassSizeConfig} = useAcademicCalendars();

const config = ref<AcademicClassConfigPayload>();
const form = useForm<AcademicClassConfigPayload>({
    students_per_class: null,
    academic_calendar_id: null,
    department_level_id: null,
    department_course_id: null,
    mode_of_study_id: null,
});

const { modals } = useModalStore();

watch(modals!, () => {
    config.value = getModalEdit(APP_MODULE_KEYS.student_per_class);
    form.defaults();
    form.department_level_id = config.value?.department_level_id ?? null;
    form.department_course_id = config.value?.department_course_id ?? null;
    form.mode_of_study_id = config.value?.mode_of_study_id ?? null;
    form.students_per_class = config.value?.students_per_class ?? null;
});

const submitForm = () => {
    storePerClassSizeConfig(
        form,
        props.institutionDepartmentId,
        String(config.value?.academic_calendar_id ?? ''),
        () => {
            window.location.reload();
        },
    );
};

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
