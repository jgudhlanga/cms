<script setup lang="ts">
import SelectCalendarYear from '@/components/academicCalendars/SelectCalendarYear.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { AcademicCalendar, AcademicCalendarOption, AcademicCalendarParams } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    academicCalendarOptions: AcademicCalendarOption[];
    academicCalendars: AcademicCalendar;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();

const { breadcrumbs } = useAcademicCalendars();
const calendarYearOption = ref<SelectOption | null>(null);
const calendarTypeOption = ref<SelectOption | null>(null);

const form = useForm<AcademicCalendarParams>({
    academic_calendar_option_id: null,
    calendar_year: null,
    opening_date: null,
    closing_date: null,
});
const submitForm = () => {};
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="submitForm" class="flex flex-col">
            <table class="j-table">
                <thead class="j-thead">
                    <tr class="j-th">
                        <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.calendar_year', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.opening_date', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.closing_date', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('trans.intake_period', 2) }}</th>
                        <th class="j-th text-center">{{ $tChoice('trans.action', 1) }}</th>
                    </tr>
                </thead>
                <tbody class="j-tbody">
                    <tr class="j-tr">
                        <td class="j-td">
                            <AcademicCalendarOptionComboSelect :data="academicCalendarOptions" v-model="calendarTypeOption" :show-label="false" />
                        </td>
                        <td class="j-td">
                            <SelectCalendarYear v-model="calendarYearOption" :show-label="false" />
                        </td>
                        <td class="j-td">
                            <OpeningDate v-model="form.opening_date" :show-label="false" />
                        </td>
                        <td class="j-td">
                            <ClosingDate v-model="form.closing_date" :show-label="false" />
                        </td>
                        <td class="j-td"></td>
                        <td class="j-td text-center"></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </PageContainer>
</template>
