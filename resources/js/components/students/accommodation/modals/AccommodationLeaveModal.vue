<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import InputError from '@/components/core/form/InputError.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Textarea } from '@/components/ui/textarea';
import { SizeVariant } from '@/enums/sizes';
import { closeModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { TextFieldType } from '@/enums/inputs';

const props = defineProps<{
    save: (payload: {
        leaveType: string;
        fromDate: string;
        toDate: string;
        reason: string;
    }) => Promise<boolean>;
}>();

const form = useForm({
    leaveType: 'Home Visit',
    fromDate: '',
    toDate: '',
    reason: '',
});

const onClose = (): void => {
    form.reset();
    form.clearErrors();
};

const save = async (): Promise<void> => {
    form.clearErrors();

    if (!form.leaveType.trim()) {
        form.setError('leaveType', trans('validation.required', { attribute: trans('trans.type') }));
        return;
    }

    if (!form.fromDate) {
        form.setError('fromDate', trans('validation.required', { attribute: trans('trans.from') }));
        return;
    }

    if (!form.toDate) {
        form.setError('toDate', trans('validation.required', { attribute: trans('trans.to') }));
        return;
    }

    form.processing = true;

    const ok = await props.save({
        leaveType: form.leaveType.trim(),
        fromDate: form.fromDate,
        toDate: form.toDate,
        reason: form.reason.trim(),
    });

    form.processing = false;

    if (ok) {
        closeModal(APP_MODULE_KEYS.hostel_accommodation_leave);
        onClose();
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_accommodation_leave"
        :title="$t('hms.apply_for_leave')"
        :size="SizeVariant.sm"
        :action-btn-text="'trans.submit'"
        :cancel-btn-text="'trans.cancel'"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3">
                <BaseInput
                    input-id="leaveType"
                    v-model="form.leaveType"
                    :label="$tChoice('trans.type', 1)"
                    required
                    @input="clearFormErrors(form, 'leaveType')"
                    :error="form.errors.leaveType"
                />
                <div class="grid gap-3 sm:grid-cols-2">
                    <BaseDatePicker
                        input-id="fromDate"
                        v-model="form.fromDate"
                        :label="$t('trans.from')"
                        :is-required="true"
                        @input="clearFormErrors(form, 'fromDate')"
                        :error="form.errors.fromDate"
                        :teleport="true"
                        :enable-time-picker="false"
                    />
                    <BaseDatePicker
                        input-id="toDate"
                        v-model="form.toDate"
                        :is-required="true"
                        :label="$t('trans.to')"
                        required
                        @input="clearFormErrors(form, 'toDate')"
                        :error="form.errors.toDate"
                        :enable-time-picker="false"
                        :teleport="true"
                    />
                </div>
                <div class="space-y-1">
                    <BaseInput 
                        input-id="reason"
                        :type="TextFieldType.textarea"
                        v-model="form.reason" rows="2" 
                        class="w-full" :label="$t('trans.reason')" :is-required="true" @input="clearFormErrors(form, 'reason')" :error="form.errors.reason" />
                </div>
            </div>
        </template>
    </BaseModal>
</template>
