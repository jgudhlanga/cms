<script setup lang="ts">
import ClosingDate from '@/components/academicCalendars/ClosingDate.vue';
import OpeningDate from '@/components/academicCalendars/OpeningDate.vue';
import SelectCalendarYear from '@/components/academicCalendars/SelectCalendarYear.vue';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicCalendar, AcademicCalendarParams, AcademicCalendarType } from '@/types/academic-calendar';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const academicCalendar = ref<AcademicCalendar>();
const calendarYearOption = ref<SelectOption | null>(null);
const calendarTypeOption = ref<SelectOption | null>(null);
const form = useForm<AcademicCalendarParams>({
    name: '',
    calendar_year: null,
    calendar_type: null,
    opening_date: null,
    closing_date: null,
    description: '',
});

const { saveAcademicCalendar } = useAcademicCalendars();

const { modals } = useModalStore();

watch(modals!, () => {
    academicCalendar.value = getModalEdit(APP_MODULE_KEYS.academic_calendars);
    form.name = academicCalendar.value?.attributes?.name ?? '';
    form.description = academicCalendar.value?.attributes?.description ?? '';
    calendarTypeOption.value = academicCalendar.value?.attributes?.calendarType
        ? { value: academicCalendar.value?.attributes?.calendarType, label: academicCalendar.value?.attributes?.calendarType }
        : null;
    calendarYearOption.value = academicCalendar.value?.attributes?.calendarYear
        ? { value: academicCalendar.value?.attributes?.calendarYear, label: academicCalendar.value?.attributes?.calendarYear }
        : null;
    form.opening_date = academicCalendar.value?.attributes?.openingDate ?? null;
    form.closing_date = academicCalendar.value?.attributes?.closingDate ?? null;
    form.defaults();
});

const save = () => {
    form.calendar_type = calendarTypeOption.value?.value as AcademicCalendarType;
    form.calendar_year = String(calendarYearOption.value?.value);
    saveAcademicCalendar(form, academicCalendar.value);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.academic_calendars"
        :title="`${academicCalendar ? $t('trans.update') : $t('trans.create')} ${$tChoice('academic_calendar.academic_calendar', 1)}`"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <SelectCalendarType v-model="calendarTypeOption" />
                <SelectCalendarYear v-model="calendarYearOption" />
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <OpeningDate v-model="form.opening_date" />
                <ClosingDate v-model="form.closing_date" />
            </div>
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
