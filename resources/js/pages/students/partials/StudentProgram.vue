<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { StudentProgramEdit } from '@/types/students';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const form = useForm<StudentProgramEdit>({
    institution_department_id: null,
    department_level_id: null,
    department_course_id: null,
    mode_of_study_id: null,
    intake_period_id: null,
    department_application_step_id: null,
});

const { modals } = useModalStore();

const modeOfStudy = ref<SelectOption | null>(null);
const department = ref<SelectOption | null>(null);
const course = ref<SelectOption | null>(null);
const level = ref<SelectOption | null>(null);

watch(modals!, () => {});

const save = () => {};
</script>

<template>
    <BaseModal :name="APP_MODULE_KEYS.student_programs" title="Student Program" :on-form-action="() => save()" :form="form">
        <template #body>
            <AdminInstitutionDepartmentComboSelect
                :form="form"
                v-model="department"
                :error="form.errors.institution_department_id"
                :is-required="true"
            />
            <!--            <DepartmentLevelComboSelect
                :form="form"
                :institution-department-id="department?.value?.toString() ?? ''"
                :allowed-levels="allowedLevels ?? []"
                v-model="level"
                :error="form.errors.level"
                :is-required="true"
            />
            <DepartmentCourseComboSelect
                :form="form"
                :department-level-id="level?.value?.toString() ?? ''"
                v-model="course"
                :error="form.errors.course"
                :is-required="true"
                :disabled="courseDisabled"
            />
            <ModeOfStudyComboSelect
                :form="form"
                v-model="modeOfStudy"
                :error="form.errors.modeOfStudy"
                :is-required="true"
                :department-course-id="course?.value?.toString() ?? ''"
                :department-level-id="level?.value?.toString() ?? ''"
            />-->
        </template>
    </BaseModal>
</template>
