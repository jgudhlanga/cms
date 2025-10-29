<script setup lang="ts">
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
}

const props = defineProps<Props>();

const { application } = props;

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    {
        title: application.attributes.department,
        href: route('enrolments.department-applications', { institution_department: String(application?.attributes.institutionDepartmentId) }),
    },
    {
        title: application.attributes.level,
        href: route('enrolments.department-applications', { institution_department: String(application?.attributes.institutionDepartmentId) }),
    },
    {
        title: 'class lists',
        href: route('enrolments.class-lists', {
            institution_department: String(application?.attributes.institutionDepartmentId),
            department_level: String(application?.attributes.departmentLevelId),
            intake_period_id: String(application?.attributes.intakePeriodId),
            mode_of_study_id: String(application?.attributes.modeOfStudyId),
            department_course_id: String(application?.attributes.departmentCourseId),
        }),
    },
    { title: application?.attributes?.studentName },
];
</script>

<template>
    <Head :title="$tChoice('trans.enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs"> </PageContainer>
</template>
