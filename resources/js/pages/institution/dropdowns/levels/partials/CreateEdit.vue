<script setup lang="ts">
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useLevels } from '@/composables/institution/useLevels';
import { TextFieldType } from '@/enums/inputs';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Level, LevelParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const level = ref<Level>();
const calendarTypeOption = ref<SelectOption | null>(null);
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
const form = useForm<LevelParams>({
    name: '',
    calendar_type: 'semester',
    description: '',
    allowed_applications_per_level: '',
    show_on_current_application_period: false,
    has_application_fee_payment: false,
});

const { saveLevel } = useLevels();
const { isItTrue } = useUtils();

const { modals } = useModalStore();

watch(modals!, () => {
    level.value = getModalEdit(APP_MODULE_KEYS.levels);
    form.name = level.value?.attributes?.name ?? '';
    form.calendar_type = level.value?.attributes?.calendarType ?? 'semester';
    form.description = level.value?.attributes?.description ?? '';
    form.allowed_applications_per_level = Number(level.value?.attributes?.allowedApplicationsPerLevel) ?? '';
    form.show_on_current_application_period = isItTrue(level.value?.attributes?.showOnCurrentApplicationPeriod) ?? false;
    form.has_application_fee_payment = isItTrue(level.value?.attributes?.hasApplicationFeePayment) ?? false;
    calendarTypeOption.value = calendarTypeOptions.value.find((option) => option.value === form.calendar_type) ?? null;
    form.defaults();
});

watch(calendarTypeOption, () => {
    form.calendar_type = (calendarTypeOption.value?.value as LevelParams['calendar_type']) ?? 'semester';
    clearFormErrors(form, 'calendar_type');
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.levels"
        :title="`${level ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.level', 1)}`"
        :on-form-action="() => saveLevel(form, level)"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-6">
                <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
                <BaseCombobox
                    v-model="calendarTypeOption"
                    :label="$tChoice('academic_calendar.calendar_type', 1)"
                    :options="calendarTypeOptions"
                    :error="form.errors.calendar_type"
                />
                <BaseCheckbox
                    input-id="show_on_current_application_period"
                    v-model="form.show_on_current_application_period"
                    :label="$t('trans.show_on_current_application_period')"
                />
                <BaseCheckbox
                    input-id="has_application_fee_payment"
                    v-model="form.has_application_fee_payment"
                    :label="$t('trans.has_application_fee_payment')"
                />
                <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
                <BaseInput
                    :label="$t('trans.allowed_applications_per_level')"
                    v-model="form.allowed_applications_per_level"
                    :type="TextFieldType.number"
                    input-id="allowed_applications_per_level"
                />
            </div>
        </template>
    </BaseModal>
</template>
