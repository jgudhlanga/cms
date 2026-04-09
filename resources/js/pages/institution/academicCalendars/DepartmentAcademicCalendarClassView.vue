<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { AcademicCalendar, AcademicCalendarClassDetail } from '@/types/academic-calendar';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    academicCalendarClass: AcademicCalendarClassDetail;
}>();

const { department, academicCalendarClass } = props;

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.departmentCode, href: route('institution-departments.show', String(department.id)) },
    { transChoiceKey: 'class' },
    { title: academicCalendarClass.name },
];
</script>

<template>
    <Head :title="academicCalendarClass.name" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution-departments.show', String(department.id))">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="academicCalendarClass.name">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <LabelValue :label="$tChoice('trans.student', 2)" :value="String(academicCalendarClass.studentCount)" />
                    <LabelValue :label="$t('academic_calendar.description')" :value="academicCalendarClass.description ?? '---'" />
                </div>
            </BaseCard>

            <BaseCard title="Class metadata">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <LabelValue v-for="meta in academicCalendarClass.metadata" :key="meta.key" :label="meta.label" :value="meta.value || '---'" />
                </div>
            </BaseCard>

            <BaseCard :title="$tChoice('trans.student', 2)">
                <table class="j-table">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">#</th>
                            <th class="j-th text-left">{{ $t('enrolment.application_number') }}</th>
                            <th class="j-th text-left">{{ $tChoice('trans.student', 1) }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr class="j-tr" v-for="(student, index) in academicCalendarClass.students" :key="student.studentProgramId">
                            <td class="j-td">{{ index + 1 }}</td>
                            <td class="j-td">{{ student.applicationTrackingNumber ?? '---' }}</td>
                            <td class="j-td">{{ student.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>
        </div>
    </PageContainer>
</template>
