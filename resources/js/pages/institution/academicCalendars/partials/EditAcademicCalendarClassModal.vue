<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import InputError from '@/components/core/form/InputError.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { SizeVariant } from '@/enums/sizes';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    modalName: string;
    onFormAction: () => void;
    onCloseModal: () => void;
}>();

const form = defineModel<InertiaForm<{ name: string; description: string | null }>>('form', { required: true });

const descriptionModel = computed({
    get: () => form.value.description ?? '',
    set: (value: string) => {
        form.value.description = value === '' ? null : value;
    },
});
</script>

<template>
    <BaseModal
        :name="modalName"
        :title="$t('academic_calendar.update_class_modal_title')"
        :form="form"
        :on-form-action="onFormAction"
        :on-close-modal="onCloseModal"
        :size="SizeVariant.sm"
        cancel-btn-text="trans.close"
        action-btn-text="academic_calendar.update_class_submit"
    >
        <template #body>
            <div class="flex flex-col gap-4">
                <BaseInput
                    v-model="form.name"
                    input-id="academic_calendar_class_name"
                    :label="$tChoice('trans.name', 1)"
                    :label-uppercase="true"
                    :error="form.errors.name"
                    :is-required="true"
                />
                <div class="flex w-full flex-col space-y-2">
                    <Label class="text-xs font-bold uppercase" for="academic_calendar_class_description">
                        {{ $t('academic_calendar.description') }}
                    </Label>
                    <Textarea
                        id="academic_calendar_class_description"
                        v-model="descriptionModel"
                        rows="4"
                        :class="{ 'border-destructive': form.errors.description }"
                    />
                    <InputError :message="form.errors.description" />
                </div>
            </div>
        </template>
    </BaseModal>
</template>
