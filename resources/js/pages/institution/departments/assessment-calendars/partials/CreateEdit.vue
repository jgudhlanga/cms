<script setup lang="ts">
import { TextFieldType } from '@/enums/inputs';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { formatIsoDateRange } from '@/lib/departmentAssessmentCalendars';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { DepartmentAssessmentCalendarRow } from '@/types/department-assessment-calendars';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    departmentId: number | string;
}>();

const row = ref<DepartmentAssessmentCalendarRow | null>(null);

const form = useForm({
    assessment_calendar_id: '' as number | string,
    start_date: '',
    end_date: '',
    first_notification_days_before: '' as number | string,
    second_notification_days_before: '' as number | string,
    due_notification_days_before: '' as number | string,
    notes: '',
});

const localToday = (): string => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
};

const activeDepartmentCalendar = computed(() =>
    row.value?.departmentCalendar && !row.value.departmentCalendar.trashed ? row.value.departmentCalendar : null,
);
const isEditing = computed(() => activeDepartmentCalendar.value !== null);
const isStartLocked = computed(() => Boolean(activeDepartmentCalendar.value?.started));
const earliestEndDate = computed(() => {
    const today = localToday();
    const start = form.start_date || row.value?.globalStartDate || today;

    return start > today ? start : today;
});

const { modals } = useModalStore();

watch(modals!, () => {
    row.value = getModalEdit(APP_MODULE_KEYS.department_assessment_calendars) ?? null;
    const departmentCalendar = activeDepartmentCalendar.value;
    const today = localToday();
    const globalStart = row.value?.globalStartDate ?? '';

    form.assessment_calendar_id = row.value?.globalCalendarId ?? '';
    form.start_date = departmentCalendar?.startDate ?? (globalStart > today ? globalStart : today);
    form.end_date = departmentCalendar?.endDate ?? row.value?.globalEndDate ?? '';
    form.first_notification_days_before = departmentCalendar?.firstNotificationDaysBefore ?? '';
    form.second_notification_days_before = departmentCalendar?.secondNotificationDaysBefore ?? '';
    form.due_notification_days_before = departmentCalendar?.dueNotificationDaysBefore ?? '';
    form.notes = departmentCalendar?.notes ?? '';
    form.defaults();
    form.clearErrors();
});

const modalTitle = computed(() =>
    trans(
        isEditing.value
            ? 'academic_calendar.department_assessment_calendar_edit_dates'
            : 'academic_calendar.department_assessment_calendar_set_dates',
    ),
);

const save = () => {
    const success = trans('academic_calendar.department_assessment_calendar_saved');
    const error = trans('trans.item_save_failure', {
        item: trans('academic_calendar.department_assessment_calendar_department_window'),
    });
    const options = buildFormOptions(form, success, error, APP_MODULE_KEYS.department_assessment_calendars);
    const departmentCalendar = activeDepartmentCalendar.value;

    if (departmentCalendar) {
        form.put(
            route('department-assessment-calendars.update', {
                department: String(props.departmentId),
                department_assessment_calendar: String(departmentCalendar.id),
            }),
            options,
        );

        return;
    }

    form.post(route('department-assessment-calendars.store', { department: String(props.departmentId) }), options);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_assessment_calendars"
        :title="modalTitle"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.lg"
    >
        <template #body>
            <div v-if="row" class="space-y-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-foreground">
                        {{ row.assessmentTypeName }}
                        <span class="font-normal text-muted-foreground">· {{ row.academicCalendarLabel }}</span>
                    </p>
                    <p id="department-calendar-college-window" class="text-xs text-muted-foreground">
                        {{ $t('academic_calendar.department_assessment_calendar_college_window') }}:
                        {{ formatIsoDateRange(row.globalStartDate, row.globalEndDate) }}
                    </p>
                </div>

                <p
                    v-if="form.errors.assessment_calendar_id"
                    role="alert"
                    class="rounded-md border border-destructive/40 bg-destructive/5 px-3 py-2 text-sm text-destructive"
                >
                    {{ form.errors.assessment_calendar_id }}
                </p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <BaseDatePicker
                        input-id="department_calendar_start_date"
                        :label="$t('trans.start_date')"
                        :enable-time-picker="false"
                        v-model="form.start_date"
                        :is-required="true"
                        :teleport="true"
                        :disabled="isStartLocked"
                        :min-date="row.globalStartDate"
                        :max-date="row.globalEndDate"
                        :error="form.errors.start_date"
                        aria-describedby="department-calendar-college-window"
                        @update:model-value="clearFormErrors(form, 'start_date')"
                    />
                    <BaseDatePicker
                        input-id="department_calendar_end_date"
                        :label="$t('trans.end_date')"
                        :enable-time-picker="false"
                        v-model="form.end_date"
                        :is-required="true"
                        :teleport="true"
                        :min-date="earliestEndDate"
                        :max-date="row.globalEndDate"
                        :error="form.errors.end_date"
                        aria-describedby="department-calendar-college-window"
                        @update:model-value="clearFormErrors(form, 'end_date')"
                    />
                </div>
                <p v-if="isStartLocked" class="text-xs text-muted-foreground">
                    {{ $t('academic_calendar.department_assessment_calendar_started_immutable') }}
                </p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <BaseInput
                        input-id="department_calendar_first_notification_days_before"
                        :label="$t('trans.assessment_calendar_first_notification_days')"
                        v-model="form.first_notification_days_before"
                        :type="TextFieldType.number"
                        min="0"
                        :placeholder="String(row.defaultNotificationDays.first)"
                        :error="form.errors.first_notification_days_before"
                        aria-describedby="department-calendar-notification-hint"
                        @input="clearFormErrors(form, 'first_notification_days_before')"
                    />
                    <BaseInput
                        input-id="department_calendar_second_notification_days_before"
                        :label="$t('trans.assessment_calendar_second_notification_days')"
                        v-model="form.second_notification_days_before"
                        :type="TextFieldType.number"
                        min="0"
                        :placeholder="String(row.defaultNotificationDays.second)"
                        :error="form.errors.second_notification_days_before"
                        aria-describedby="department-calendar-notification-hint"
                        @input="clearFormErrors(form, 'second_notification_days_before')"
                    />
                    <BaseInput
                        input-id="department_calendar_due_notification_days_before"
                        :label="$t('trans.assessment_calendar_due_notification_days')"
                        v-model="form.due_notification_days_before"
                        :type="TextFieldType.number"
                        min="0"
                        :placeholder="String(row.defaultNotificationDays.due)"
                        :error="form.errors.due_notification_days_before"
                        aria-describedby="department-calendar-notification-hint"
                        @input="clearFormErrors(form, 'due_notification_days_before')"
                    />
                </div>
                <p id="department-calendar-notification-hint" class="text-xs text-muted-foreground">
                    {{
                        $t('academic_calendar.department_assessment_calendar_notification_hint', {
                            first: String(row.defaultNotificationDays.first),
                            second: String(row.defaultNotificationDays.second),
                            due: String(row.defaultNotificationDays.due),
                        })
                    }}
                </p>

                <BaseInput
                    input-id="department_calendar_notes"
                    :label="$t('academic_calendar.department_assessment_calendar_notes')"
                    v-model="form.notes"
                    maxlength="1000"
                    :error="form.errors.notes"
                    @input="clearFormErrors(form, 'notes')"
                />
            </div>
        </template>
    </BaseModal>
</template>
