<script setup lang="ts">
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import Description from '@/components/core/form/text/Description.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicCalendar, AcademicCalendarParams, AcademicCalendarType } from '@/types/academic-calendar';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const academicCalendar = ref<AcademicCalendar>();
const typeModel = ref<SelectOption | null>(null);
const { saveAcademicCalendar } = useAcademicCalendars();
const form = useForm<AcademicCalendarParams>({
    name: '',
    type: 'semester' as typeof AcademicCalendarType,
    closing_date: '',
    opening_date: '',
    description: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    academicCalendar.value = getModalEdit(APP_MODULE_KEYS.academic_calendars);
    form.name = academicCalendar.value?.attributes?.name ?? '';
    form.opening_date = academicCalendar.value?.attributes?.openingDate ?? '';
    form.closing_date = academicCalendar.value?.attributes?.closingDate ?? '';
    typeModel.value = {
        value: academicCalendar.value?.attributes?.type ?? 'semester',
        label:
            (academicCalendar.value?.attributes?.type
                ? academicCalendar.value?.attributes?.type.charAt(0).toUpperCase() + academicCalendar.value?.attributes?.type.slice(1)
                : 'Semester') ?? 'Semester',
    };
    form.type = (academicCalendar.value?.attributes?.type as typeof AcademicCalendarType) ?? 'semester';
    form.description = academicCalendar.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.academic_calendars"
        :title="`${academicCalendar ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.academic_year', 1)}`"
        :on-form-action="() => saveAcademicCalendar(form, academicCalendar)"
        :form="form"
        :size="SizeVariant.sm"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-6">
                <BaseInput
                    input-id="name"
                    :is-required="true"
                    :label-uppercase="true"
                    :label="$tChoice('trans.name', 1)"
                    :inputAutoFocus="true"
                    v-model="form.name"
                    @input="clearFormErrors(form, 'name')"
                    :error="form.errors.name"
                />
                <TermTypeComboSelect class="flex w-full" v-model="typeModel" :is-required="true" :label-uppercase="true" />
                <BaseDatePicker
                    input-id="opening_date"
                    :label="$tChoice('trans.opening_date', 1)"
                    :enable-time-picker="false"
                    :label-uppercase="true"
                    v-model="form.opening_date"
                    :is-required="true"
                    :teleport="true"
                    :error="form.errors.opening_date"
                    @update:model-value="clearFormErrors(form, 'opening_date')"
                />
                <BaseDatePicker
                    input-id="closing_date"
                    :label="$tChoice('trans.closing_date', 1)"
                    :enable-time-picker="false"
                    :label-uppercase="true"
                    v-model="form.closing_date"
                    :is-required="true"
                    :teleport="true"
                    :error="form.errors.closing_date"
                    @update:model-value="clearFormErrors(form, 'closing_date')"
                />
                <Description :label-uppercase="true" v-model="form.description" />
            </div>
        </template>
    </BaseModal>
</template>
