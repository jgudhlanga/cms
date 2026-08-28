<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import { BaseButton } from '@/components/core/button';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { useStudentShowNavigation } from '@/composables/students/useStudentShowNavigation';
import { useStudents } from '@/composables/students/useStudents';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { buildStudentShowBreadcrumbs, buildStudentShowUrl } from '@/lib/studentShowNavigation';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Student, StudentApplicationEdit } from '@/types/students';
import { Link } from '@/types/ui';
import { User } from '@/types/users';
import type { SelectOption } from '@/types/utils';

interface Props {
    user: User;
    student: Student | null;
    program: Enrolment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { student, program } = props;

const { query, navigationOptions, backUrl } = useStudentShowNavigation();

const studentShowUrl = computed(() =>
    student ? buildStudentShowUrl(student.id, { ...navigationOptions.value, tab: 'applications' }) : route('students.index'),
);

const exitUrl = computed(() => (query.value.return ? backUrl.value : studentShowUrl.value));

const toSelectOption = (id: string | number | null | undefined, label: string | null | undefined): SelectOption | null => {
    if (id === null || id === undefined || id === '') {
        return null;
    }

    const value = Number(id);

    if (!Number.isFinite(value) || value <= 0) {
        return null;
    }

    return { value, label: label ?? '' };
};

const breadcrumbs = computed((): Array<Link> => {
    const parentCrumbs = buildStudentShowBreadcrumbs(query.value.from).slice(0, -1);

    return [
        ...parentCrumbs,
        { title: student?.attributes?.name ?? props.user.attributes.name ?? '', href: studentShowUrl.value },
        { title: 'Edit Program' },
    ];
});

const { navigateTo } = useUtils();
const { programFormSchema } = useStudentPortal();
const { updateProgram } = useStudents();

const isLoading = ref(false);
const form = useForm<StudentApplicationEdit>({
    institution_department_id: null,
    department_level_id: null,
    department_course_id: null,
    mode_of_study_id: null,
    department: null,
    level: null,
    course: null,
    modeOfStudy: null,
});

onMounted(() => {
    form.modeOfStudy = toSelectOption(program?.attributes?.modeOfStudyId, program?.attributes?.modeOfStudy);
    form.department = toSelectOption(program?.attributes?.institutionDepartmentId, program?.attributes?.department);
    form.level = toSelectOption(program?.attributes?.departmentLevelId, program?.attributes?.level);
    form.course = toSelectOption(program?.attributes?.departmentCourseId, program?.attributes?.course);
});

const save = async () => {
    form.institution_department_id = String(form.department?.value);
    form.department_level_id = String(form.level?.value);
    form.department_course_id = String(form.course?.value);
    form.mode_of_study_id = String(form.modeOfStudy?.value);
    try {
        programFormSchema().parse(form);
        updateProgram(String(program.id), form);
    } catch (error: any) {
        if (error?.format) {
            form.setError(error.format());
        } else {
            console.error(error);
        }
    }
};

watch(
    () => form.department?.value,
    (newVal, oldVal) => {
        if (!oldVal || newVal === oldVal) return;
        form.level = null;
        form.course = null;
        clearFormErrors(form, 'level');
        clearFormErrors(form, 'course');
    },
);

watch(
    () => form.level?.value,
    (newVal, oldVal) => {
        if (!oldVal || newVal === oldVal) return;
        form.course = null;
        clearFormErrors(form, 'course');
    },
);
</script>

<template>
    <Head :title="$tChoice('student', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="exitUrl">
        <form @submit.prevent="() => save()">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <AdminInstitutionDepartmentComboSelect :form="form" v-model="form.department" :error="form.errors.department" :is-required="true" />
                <AdminDepartmentLevelComboSelect
                    :form="form"
                    :institution-department-id="String(form.department?.value)"
                    v-model="form.level"
                    :error="form.errors.level"
                    :is-required="true"
                />
                <AdminDepartmentCourseComboSelect
                    :form="form"
                    :department-level-id="String(form.level?.value)"
                    v-model="form.course"
                    :error="form.errors.course"
                    :is-required="true"
                />
                <ModeOfStudyComboSelect :form="form" v-model="form.modeOfStudy" :error="form.errors.modeOfStudy" :is-required="true" />
            </div>
            <div class="my-6 flex flex-col justify-center space-y-3 space-x-3 md:flex-row">
                <BaseButton
                    @click="navigateTo(exitUrl)"
                    type="button"
                    :variant="ColorVariant.shade"
                    class="w-full md:w-50"
                    :size="ButtonSize.xl"
                >
                    {{ $t('trans.cancel') }}
                </BaseButton>
                <BaseButton class="w-full md:w-50" :size="ButtonSize.xl" :processing="isLoading">
                    {{ $t('trans.submit') }}
                </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
