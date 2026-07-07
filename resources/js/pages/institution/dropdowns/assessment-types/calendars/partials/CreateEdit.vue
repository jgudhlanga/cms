<script setup lang="ts">
import AcademicCalendarComboSelect from '@/components/core/form/combobox/AcademicCalendarComboSelect.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import {
    buildCalendarTypeOptions,
    defaultCalendarTypeOption,
    resolveAcademicCalendarOption,
    resolveCalendarTypeOption,
    useAssessmentCalendars,
} from '@/composables/institution/useAssessmentCalendars';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicCalendar } from '@/types/academic-calendar';
import { AssessmentCalendar, AssessmentCalendarParams, AssessmentType } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    assessmentType: AssessmentType;
    academicCalendars: AcademicCalendar[];
}>();

const assessmentCalendar = ref<AssessmentCalendar>();
const academicCalendarOption = ref<SelectOption | null>(null);
const calendarTypeOption = ref<SelectOption | null>(defaultCalendarTypeOption());

const form = useForm<AssessmentCalendarParams>({
    academic_calendar_id: '',
    start_date: '',
    end_date: '',
    type: 'semester',
});

const { saveAssessmentCalendar, formSchema } = useAssessmentCalendars(props.assessmentType);
const typeOptions = computed(() => buildCalendarTypeOptions());

const { modals } = useModalStore();

watch(modals!, () => {
    assessmentCalendar.value = getModalEdit(APP_MODULE_KEYS.assessment_type_calendars);

    if (assessmentCalendar.value) {
        academicCalendarOption.value = resolveAcademicCalendarOption(
            props.academicCalendars,
            assessmentCalendar.value.attributes?.academicCalendarId,
        );
        calendarTypeOption.value = resolveCalendarTypeOption(assessmentCalendar.value.attributes?.type);
        form.start_date = assessmentCalendar.value.attributes?.startDate ?? '';
        form.end_date = assessmentCalendar.value.attributes?.endDate ?? '';
    } else {
        academicCalendarOption.value = null;
        calendarTypeOption.value = defaultCalendarTypeOption();
        form.start_date = '';
        form.end_date = '';
    }

    form.academic_calendar_id = academicCalendarOption.value ? String(academicCalendarOption.value.value) : '';
    form.type = String(calendarTypeOption.value?.value ?? 'semester');
    form.defaults();
});

const save = () => {
    form.academic_calendar_id = academicCalendarOption.value ? String(academicCalendarOption.value.value) : '';
    form.type = String(calendarTypeOption.value?.value ?? 'semester');

    const result = formSchema().safeParse(form.data());
    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;
        const formattedErrors: Record<keyof AssessmentCalendarParams, string> = {
            academic_calendar_id: '',
            start_date: '',
            end_date: '',
            type: '',
        };

        (Object.keys(fieldErrors) as (keyof typeof fieldErrors)[]).forEach((key) => {
            const errors = fieldErrors[key];
            if (errors && errors.length > 0) {
                formattedErrors[key as keyof AssessmentCalendarParams] = errors[0];
            }
        });

        form.setError(formattedErrors);
        return;
    }

    saveAssessmentCalendar(form, assessmentCalendar.value);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.assessment_type_calendars"
        :title="`${assessmentCalendar ? $t('trans.edit') : $t('trans.create')} ${$tChoice('trans.assessment_calendar', 1)}`"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <AcademicCalendarComboSelect
                :input-auto-focus="true"
                :data="academicCalendars"
                v-model="academicCalendarOption"
                :is-required="true"
                :error="form.errors.academic_calendar_id"
                @update:model-value="clearFormErrors(form, 'academic_calendar_id')"
            />
            <div class="grid grid-cols-2 gap-2">
                <BaseDatePicker
                    input-id="start_date"
                    :label="$t('trans.start_date')"
                    :enable-time-picker="false"
                    v-model="form.start_date"
                    :is-required="true"
                    :teleport="true"
                    :error="form.errors.start_date"
                    @update:model-value="clearFormErrors(form, 'start_date')"
                />
                <BaseDatePicker
                    input-id="end_date"
                    :label="$t('trans.end_date')"
                    :enable-time-picker="false"
                    v-model="form.end_date"
                    :is-required="true"
                    :teleport="true"
                    :error="form.errors.end_date"
                    @update:model-value="clearFormErrors(form, 'end_date')"
                />
            </div>
            <BaseCombobox
                :label="$tChoice('trans.type', 1)"
                v-model="calendarTypeOption"
                :options="typeOptions"
                :is-required="true"
                :error="form.errors.type"
                @update:model-value="clearFormErrors(form, 'type')"
            />
        </template>
    </BaseModal>
</template>
