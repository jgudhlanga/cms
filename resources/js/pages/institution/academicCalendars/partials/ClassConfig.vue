<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { errorAlert, getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicClassConfigPayload } from '@/types/academic-calendar';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface Props {
    institutionDepartmentId: string;
}

const props = defineProps<Props>();

const { storePerClassSizeConfig } = useAcademicCalendars();

const config = ref<AcademicClassConfigPayload>();
const form = useForm<AcademicClassConfigPayload>({
    students_per_class: null,
    academic_calendar_id: null,
    department_level_id: null,
    department_course_id: null,
    mode_of_study_id: null,
    academic_year_option_id: null,
});

const modalStore = useModalStore();

const yearOptions = ref<SelectOption[]>([]);
const yearOptionsLoading = ref(false);

type AcademicYearOptionApiRow = {
    id: number;
    attributes: {
        name: string;
    };
};

/** When level has no calendar type, semester options are loaded (matches backend default). */
const loadYearOptions = async (calendarType: string | null | undefined): Promise<void> => {
    yearOptionsLoading.value = true;
    yearOptions.value = [];
    const ct = (calendarType ?? 'semester').trim() || 'semester';
    try {
        const base = route('v1.academic-year-options.index');
        const sep = base.includes('?') ? '&' : '?';
        const url = `${base}${sep}calendar_type=${encodeURIComponent(ct)}&page_size=all`;
        const body = await HttpService.get(url);
        const rows = (body?.data ?? []) as AcademicYearOptionApiRow[];
        yearOptions.value = rows.map((row) => ({
            value: String(row.id),
            label: row.attributes?.name ?? '',
        }));
    } catch {
        yearOptions.value = [];
    } finally {
        yearOptionsLoading.value = false;
    }
};

watch(
    () => modalStore.modals?.[APP_MODULE_KEYS.student_per_class]?.opened,
    async (opened) => {
        if (!opened) {
            return;
        }
        const edit = getModalEdit(APP_MODULE_KEYS.student_per_class) as AcademicClassConfigPayload | undefined;
        if (edit == null) {
            return;
        }
        config.value = edit;
        form.clearErrors();
        form.academic_calendar_id = edit.academic_calendar_id ?? null;
        form.department_level_id = edit.department_level_id ?? null;
        form.department_course_id = edit.department_course_id ?? null;
        form.mode_of_study_id = edit.mode_of_study_id ?? null;
        form.students_per_class = edit.students_per_class ?? null;
        form.academic_year_option_id = null;

        await loadYearOptions(edit.calendarType ?? 'semester');

        const preferred = edit.academic_year_option_id != null && edit.academic_year_option_id !== ''
            ? String(edit.academic_year_option_id)
            : null;
        if (preferred !== null && yearOptions.value.some((o) => o.value === preferred)) {
            form.academic_year_option_id = preferred;
        } else if (yearOptions.value.length > 0) {
            form.academic_year_option_id = yearOptions.value[0].value;
        } else {
            form.academic_year_option_id = null;
        }
    },
);

const submitForm = (): void => {
    const calendarId = String(form.academic_calendar_id ?? config.value?.academic_calendar_id ?? '').trim();
    if (!calendarId) {
        errorAlert(
            trans('trans.required_field', {
                field: trans_choice('academic_calendar.academic_calendar', 1),
            }),
        );
        return;
    }
    const optionId = form.academic_year_option_id;
    if (optionId === null || optionId === undefined || String(optionId).trim() === '') {
        errorAlert(
            trans('trans.required_field', {
                field: trans_choice('academic_years.calendar_year_option', 1),
            }),
        );
        return;
    }
    storePerClassSizeConfig(form, props.institutionDepartmentId, calendarId);
};

</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_per_class"
        :title="$tChoice('academic_calendar.class_unit_size', 1)"
        :on-form-action="() => submitForm()"
        :form="form"
    >
        <template #body>
            <BaseSelect
                class="mb-4 w-full"
                :label="$tChoice('academic_years.calendar_year_option', 1)"
                placeholder=""
                :options="yearOptions"
                :loading="yearOptionsLoading"
                v-model="form.academic_year_option_id"
                :is-searchable="false"
                @update:modelValue="clearFormErrors(form, 'academic_year_option_id')"
                :error="form.errors.academic_year_option_id"
            />
            <BaseInput
                input-id="students_per_class"
                :inputAutoFocus="true"
                v-model="form.students_per_class"
                @input="clearFormErrors(form, 'students_per_class')"
                :error="form.errors.students_per_class"
            />
        </template>
    </BaseModal>
</template>
