<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { trans } from 'laravel-vue-i18n';

defineProps<{
    currentId: string;
    modelValue: string;
    suggestedIdNumber: string | null;
    disabled?: boolean;
    canSave?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    save: [];
    useSuggested: [];
}>();
</script>

<template>
    <div class="flex min-w-[320px] flex-wrap items-center gap-2">
        <span class="shrink-0 font-mono text-xs text-destructive">{{ currentId }}</span>
        <span class="text-muted-foreground text-xs">→</span>
        <BaseInput
            :model-value="modelValue"
            class="min-w-[140px] flex-1"
            :name="`faulty_id_number_${currentId}`"
            :placeholder="trans('trans.ui_eg_63_1234567n63')"
            :disabled="disabled"
            @update:model-value="emit('update:modelValue', $event)"
        />
        <button
            v-if="suggestedIdNumber"
            type="button"
            class="shrink-0 text-xs text-primary cursor-pointer"
            :disabled="disabled"
            @click="emit('useSuggested')"
        >
            {{ trans('trans.maintenance_faulty_data_use_suggested') }}
        </button>
        <BaseButton
            :title="trans('trans.save')"
            :variant="ColorVariant.success"
            :size="ButtonSize.xs"
            type="button"
            class="shrink-0 rounded-full capitalize"
            :disabled="disabled || !canSave"
            @click="emit('save')"
        />
    </div>
</template>
