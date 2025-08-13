<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject, DataListProps } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Enrolment } from '@/types/enrolments';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import EnrolmentItem from './EnrolmentItem.vue';
import { computed } from 'vue';
import { Link } from '@/types/ui';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    enrolments: Enrolment[];
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { department, level, enrolments } = props;
const {createCourseLevelEnrolmentColumns} = useDepartmentCourses();

const institutionDepartmentId = department?.id?.toString() ?? '';
const departmentLevelId = level?.id?.toString() ?? '';

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { transChoiceKey: 'enrolment' },
];
const firstCourseName = enrolments?.[0]?.attributes?.course ?? '';

const sortedEnrolments = computed(() => {
    return [...enrolments].sort((a, b) => {
        const totalA = a.relationships?.oLevelResults?.reduce(
            (sum, result) => sum + Number(result.attributes.gradePosition || 0),
            0
        ) ?? 0;

        const totalB = b.relationships?.oLevelResults?.reduce(
            (sum, result) => sum + Number(result.attributes.gradePosition || 0),
            0
        ) ?? 0;

        return totalA - totalB; // lowest total first
    });
});
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall v-if="firstCourseName" :title="firstCourseName" />
        <CustomSeparator classes="h-[1px] my-6" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3" v-if="enrolments.length > 0">
            <div v-for="enrolment in sortedEnrolments" :key="enrolment.id">
                <EnrolmentItem :enrolment="enrolment" />
            </div>
        </div>
        <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('trans.no_data_found_description', { data: $tChoice('trans.enrolment', 2) })"/>
    </PageContainer>
</template>
