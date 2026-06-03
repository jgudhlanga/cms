<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { BaseSelect } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Textarea } from '@/components/ui/textarea';
import { SizeVariant } from '@/enums/sizes';
import { closeModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    save: (payload: {
        category: string;
        subject: string;
        description: string;
        priority: string;
    }) => Promise<boolean>;
}>();

const form = useForm({
    subject: '',
    description: '',
});

/** vue3-select binds the option `value` string, not the full option object */
const categoryOption = ref('maintenance');
const priorityOption = ref('medium');

const categoryOptions = computed<SelectOption[]>(() => [
    { value: 'maintenance', label: trans('hms.query_category_maintenance') },
    { value: 'plumbing', label: trans('hms.query_category_plumbing') },
    { value: 'electrical', label: trans('hms.query_category_electrical') },
    { value: 'cleanliness', label: trans('hms.query_category_cleanliness') },
    { value: 'security', label: trans('hms.query_category_security') },
    { value: 'other', label: trans('hms.query_category_other') },
]);

const priorityOptions = computed<SelectOption[]>(() => [
    { value: 'low', label: trans('hms.query_priority_low') },
    { value: 'medium', label: trans('hms.query_priority_medium') },
    { value: 'high', label: trans('hms.query_priority_high') },
]);

const onClose = (): void => {
    form.reset();
    categoryOption.value = 'maintenance';
    priorityOption.value = 'medium';
    form.clearErrors();
};

const save = async (): Promise<void> => {
    form.clearErrors();

    if (!form.subject.trim()) {
        form.setError('subject', trans('validation.required', { attribute: trans('trans.subject') }));
        return;
    }

    form.processing = true;

    const ok = await props.save({
        category: categoryOption.value,
        subject: form.subject.trim(),
        description: form.description.trim(),
        priority: priorityOption.value,
    });

    form.processing = false;

    if (ok) {
        closeModal(APP_MODULE_KEYS.hostel_accommodation_query);
        onClose();
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_accommodation_query"
        :title="$t('hms.new_query')"
        :size="SizeVariant.sm"
        :action-btn-text="'trans.submit'"
        :cancel-btn-text="'trans.cancel'"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3">
                <BaseSelect
                    v-model="categoryOption"
                    :options="categoryOptions"
                    :label="$t('trans.category')"
                    :is-clearable="false"
                    :is-required="true"
                    @update:model-value="clearFormErrors(form, 'category')"
                    :error="form.errors.category"
                />
                <BaseSelect
                    v-model="priorityOption"
                    :options="priorityOptions"
                    :label="$t('trans.priority')"
                    :is-clearable="false"
                    :is-required="true"
                    @update:model-value="clearFormErrors(form, 'priority')"
                    :error="form.errors.priority"
                />
                <BaseInput
                    v-model="form.subject"
                    :label="$tChoice('trans.subject', 1)"
                    :is-required="true"
                    @input="clearFormErrors(form, 'subject')"
                    :error="form.errors.subject"
                />
                <div class="space-y-1">
                    <label class="text-sm font-medium text-foreground">{{ $t('trans.description') }}</label>
                    <Textarea v-model="form.description" rows="3" class="w-full" :is-required="true" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
                </div>
            </div>
        </template>
    </BaseModal>
</template>
