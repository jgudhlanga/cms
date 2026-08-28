<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import GenericButton from '@/components/core/button/GenericButton.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { trans } from 'laravel-vue-i18n';

interface Props {
    total: number;
    processing?: boolean;
    error?: string;
}

withDefaults(defineProps<Props>(), {
    processing: false,
    error: '',
});

const recipientEmails = defineModel<string>('recipientEmails', { default: '' });

const emit = defineEmits<{
    (e: 'export'): void;
}>();
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
            <div class="space-y-1">
                <BaseInput
                    v-model="recipientEmails"
                    name="recipient_emails"
                    :label="trans('trans.maintenance_export_recipient_emails_label')"
                    :placeholder="trans('trans.maintenance_export_recipient_emails_placeholder')"
                    :error="error"
                />
                <p class="text-xs text-muted-foreground">
                    {{ trans('trans.maintenance_export_recipient_emails_help') }}
                </p>
            </div>

            <div class="flex items-center justify-between gap-4 lg:justify-end">
                <div class="text-right">
                    <p class="text-[0.63rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                        {{ trans('trans.maintenance_export_preview_total') }}
                    </p>
                    <p class="text-lg font-semibold tabular-nums text-foreground">{{ total }}</p>
                </div>

                <GenericButton
                    :icon="IconName.mail"
                    :variant="ColorVariant.primary"
                    :title="trans('trans.maintenance_email_export')"
                    :disabled="processing || total === 0"
                    @click="emit('export')"
                />
            </div>
        </div>
    </div>
</template>
