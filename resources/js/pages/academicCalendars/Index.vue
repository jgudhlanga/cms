<script setup lang="ts">
import SelectCalendarYear from '@/components/academicCalendars/SelectCalendarYear.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { AcademicCalendar, AcademicCalendarParams } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { SelectOption } from '@/types/utils';
import { Head, useForm } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface Props {
    academicCalendars: AcademicCalendar[];
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();

const { breadcrumbs, saveAcademicCalendar } = useAcademicCalendars();
const calendarYearOption = ref<SelectOption | null>(null);
const calendarTypeOption = ref<SelectOption | null>(null);
const editCalendar = ref<AcademicCalendar | null | undefined>(undefined);
const calendarTypeOptions = computed<SelectOption[]>(() => {
    return [
        {
            label: trans_choice('academic_calendar.term', 1),
            value: 'term',
        },
        {
            label: trans_choice('academic_calendar.semester', 1),
            value: 'semester',
        },
        {
            label: trans_choice('academic_calendar.abma', 1),
            value: 'abma',
        },
    ];
});

const form = useForm<AcademicCalendarParams>({
    calendar_year: null,
    type: null,
    opening_date: null,
    closing_date: null,
});

const resetForm = () => {
    form.reset();
    calendarYearOption.value = null;
    calendarTypeOption.value = null;
};
const submitForm = () => {
    form.calendar_year = String(calendarYearOption.value?.value);
    form.type = (calendarTypeOption.value?.value as AcademicCalendarParams['type']) ?? null;
    saveAcademicCalendar(form, editCalendar.value);
    resetForm();
    editCalendar.value = null;
};

const edit = (academicCalendar: AcademicCalendar) => {
    editCalendar.value = academicCalendar;
    calendarYearOption.value = { label: academicCalendar.attributes.calendarYear, value: academicCalendar.attributes.calendarYear };
    calendarTypeOption.value = {
        label: trans_choice(`academic_calendar.${academicCalendar.attributes.type}`, 1),
        value: academicCalendar.attributes.type,
    };
    form.opening_date = academicCalendar.attributes.openingDate;
    form.closing_date = academicCalendar.attributes.closingDate;
};
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="submitForm" class="flex flex-col">
            <table class="j-table">
                <thead class="j-thead">
                    <tr class="j-th">
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.calendar_year', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.calendar_type', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.opening_date', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('academic_calendar.closing_date', 1) }}</th>
                        <th class="j-th text-center">{{ $tChoice('trans.action', 1) }}</th>
                    </tr>
                </thead>
                <tbody class="j-tbody">
                    <template v-if="academicCalendars && academicCalendars.length > 0">
                        <tr class="j-tr" v-for="calendar in academicCalendars" :key="calendar.id">
                            <td class="j-td">{{ calendar.attributes.calendarYear }}</td>
                            <td class="j-td">{{ $tChoice(`academic_calendar.${calendar.attributes.type}`, 1) }}</td>
                            <td class="j-td">{{ calendar.attributes.openingDate }}</td>
                            <td class="j-td">{{ calendar.attributes.closingDate }}</td>
                            <td class="j-td text-center">
                                <BaseButton
                                    type="button"
                                    class="rounded-full"
                                    :title="$t('trans.edit')"
                                    :variant="ColorVariant.primary_outline"
                                    :size="ButtonSize.sm"
                                    @click="() => edit(calendar)"
                                />
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr class="j-tr">
                            <td class="j-td" colspan="5"><Empty /></td></tr
                    ></template>
                    <tr class="j-tr">
                        <td class="j-td">
                            <SelectCalendarYear v-model="calendarYearOption" :show-label="false" />
                        </td>
                        <td class="j-td">
                            <BaseCombobox v-model="calendarTypeOption" :label="''" :options="calendarTypeOptions" />
                        </td>
                        <td class="j-td">
                            <OpeningDate v-model="form.opening_date" :show-label="false" class="w-50" />
                        </td>
                        <td class="j-td">
                            <ClosingDate v-model="form.closing_date" :show-label="false" class="w-50" />
                        </td>
                        <td class="j-td text-center">
                            <BaseButton
                                class="rounded-full"
                                :title="$t('trans.save')"
                                :loading="form.processing"
                                :disabled="form.processing"
                                :variant="ColorVariant.primary"
                                :size="ButtonSize.sm"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </PageContainer>
</template>
