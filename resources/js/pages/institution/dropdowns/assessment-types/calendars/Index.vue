<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import {
    isAllAssessmentCalendarLimitsReached,
    normalizeAssessmentCalendarRecords,
    useAssessmentCalendars,
} from '@/composables/institution/useAssessmentCalendars';
import { AssessmentType } from '@/types/institution';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from './partials/CreateEdit.vue';
import type { AssessmentCalendarPageProps } from '@/composables/institution/useAssessmentCalendars';

const props = defineProps<
    {
        assessmentType: AssessmentType;
        assessmentCalendars: DataListProps;
        trashedCount: number;
        filters: DataFilters;
        auth: AuthObject;
        errors: object;
    } & AssessmentCalendarPageProps
>();

const { createAssessmentCalendarColumns, breadcrumbs, onOpenModal } = useAssessmentCalendars(props.assessmentType);
const can = props?.auth?.can;

const existingRecords = computed(() => normalizeAssessmentCalendarRecords(props.existingAssessmentCalendars));

const disableCreate = computed(
    () =>
        !can['create:assessment-calendar'] ||
        isAllAssessmentCalendarLimitsReached(existingRecords.value, props.assessmentCalendarLimits),
);
</script>

<template>
    <Head :title="$tChoice('trans.assessment_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('assessment-types.index')">
        <DataTable
            :data="assessmentCalendars.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="
                route('assessment-calendars.index', {
                    assessment_type: String(assessmentType.id),
                })
            "
            :pagination="{ ...assessmentCalendars.links, ...assessmentCalendars.meta }"
            :columns="createAssessmentCalendarColumns()"
            :on-create="() => onOpenModal(can['create:assessment-calendar'])"
            :disable-create="disableCreate"
        />
        <CreateEdit
            :assessment-type="assessmentType"
            :academic-calendars="academicCalendars"
            :existing-assessment-calendars="existingAssessmentCalendars"
            :assessment-calendar-limits="assessmentCalendarLimits"
        />
    </PageContainer>
</template>
