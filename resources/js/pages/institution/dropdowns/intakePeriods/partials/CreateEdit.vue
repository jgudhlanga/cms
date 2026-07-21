<script setup lang="ts">
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseCheckbox from '@/components/core/form/radio/BaseCheckbox.vue';
import InputError from '@/components/core/form/InputError.vue';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { IntakePeriod, IntakePeriodParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const intakePeriod = ref<IntakePeriod>();
const form = useForm<IntakePeriodParams>({
    name: '',
    start_date: '',
    end_date: '',
    description: '',
    status: 'open',
    is_continuous: false,
});

const { saveIntakePeriod, formSchema, statusOptions } = useIntakePeriods();

const { modals } = useModalStore();

watch(modals!, () => {
    intakePeriod.value = getModalEdit(APP_MODULE_KEYS.intake_periods);
    form.name = intakePeriod.value?.attributes?.name ?? '';
    form.start_date = intakePeriod.value?.attributes?.startDate ?? '';
    form.end_date = intakePeriod.value?.attributes?.endDate ?? '';
    form.description = intakePeriod.value?.attributes?.description ?? '';
    form.status = intakePeriod.value?.attributes?.status ?? 'open';
    form.is_continuous = !!intakePeriod.value?.attributes?.isContinuous;
    form.defaults();
});

const save = () => {
    const result = formSchema().safeParse(form.data());
    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;
        const formattedErrors: Record<keyof IntakePeriodParams, string> = {
            name: '',
            start_date: '',
            end_date: '',
            description: '',
            status: '',
            is_continuous: '',
        };

        (Object.keys(fieldErrors) as (keyof typeof fieldErrors)[]).forEach((key) => {
            const errors = fieldErrors[key];
            if (errors && errors.length > 0) {
                formattedErrors[key as keyof IntakePeriodParams] = errors[0];
            }
        });

        form.setError(formattedErrors);
        return;
    }
    saveIntakePeriod(form, intakePeriod.value);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.intake_periods"
        :title="`${intakePeriod ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.intake_period', 1)}`"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" :is-required="true" />
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

            <BaseSelect
                :label="$tChoice('trans.status', 1)"
                v-model="form.status"
                :options="statusOptions()"
                :is-required="true"
                :error="form.errors.status"
                @update:model-value="clearFormErrors(form, 'status')"
            />

            <BaseCheckbox
                input-id="is_continuous"
                v-model="form.is_continuous"
                :label="$t('trans.intake_period_is_continuous')"
                @update:model-value="clearFormErrors(form, 'is_continuous')"
            />
            <p class="mb-3 text-xs text-muted-foreground">
                {{ $t('trans.intake_period_is_continuous_help') }}
            </p>
            <InputError v-if="form.errors.is_continuous" :message="form.errors.is_continuous" />

            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
