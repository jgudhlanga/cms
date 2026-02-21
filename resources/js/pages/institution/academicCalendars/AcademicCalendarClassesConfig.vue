<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';

import { AcademicCalendar } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { clearFormErrors } from '@/lib/forms';
import { BaseInput } from '@/components/core/form';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    auth: AuthObject;
    errors: object;
}>();

const { department, level, course, mode } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', String(department.id)) },
    { title: level.attributes.level, href: route('institution-departments.show', String(department.id)) },
    { title: course.attributes.course, href: route('institution-departments.show', String(department.id)) },
    { title: mode.attributes.name, href: route('institution-departments.show', String(department.id)) },
    { transKey: 'class_setup' },
];

const submitForm = () => {};
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col space-y-6">
            <form @submit.prevent="submitForm" class="flex flex-col">
                <BaseInput
                    input-id="local_fca_amount"
                    :label="$t('trans.amount_in_us')"
                    v-model="form.local_fca_amount"
                    @input="clearFormErrors(form, 'local_fca_amount')"
                    :error="form.errors.local_fca_amount"
                    :label-uppercase="true"
                />
            </form>
        </div>
    </PageContainer>
</template>
