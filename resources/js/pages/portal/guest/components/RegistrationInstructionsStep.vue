<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCheckbox from '@/components/core/form/radio/BaseCheckbox.vue';

const acknowledgedAdvert = defineModel<boolean>('acknowledgedAdvert', { required: true });

defineProps<{
    showValidationHint?: boolean;
}>();

const emit = defineEmits<{
    (e: 'continue'): void;
}>();

const onContinue = () => {
    if (!acknowledgedAdvert.value) {
        return;
    }

    emit('continue');
};
</script>

<template>
    <div class="flex flex-col space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-foreground">{{ $t('trans.registration_instructions_title') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ $t('trans.registration_instructions_subtitle') }}</p>
        </div>

        <section class="space-y-4 rounded-xl border border-border bg-muted/20 p-4 sm:p-5">
            <div>
                <h3 class="text-sm font-medium text-foreground">{{ $t('trans.enrollment_important_notices') }}</h3>
                <ul class="mt-3 space-y-3 text-sm leading-relaxed text-muted-foreground">
                    <li class="rounded-md border border-border/60 bg-background/60 p-3">
                        {{ $t('trans.ui_college_advert_warning') }}
                    </li>
                    <li class="rounded-md border border-border/60 bg-background/60 p-3">
                        {{ $t('trans.ui_ecocash_users_payment_device_warning') }}
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-medium text-foreground">{{ $t('trans.registration_what_you_need') }}</h3>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-muted-foreground">
                    <li>{{ $t('trans.enrollment_enter_national_id') }}</li>
                    <li>{{ $t('trans.email') }}</li>
                </ul>
            </div>
        </section>

        <fieldset>
            <legend class="sr-only">{{ $t('trans.registration_instructions_ack_legend') }}</legend>

            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border p-3">
                <BaseCheckbox
                    v-model="acknowledgedAdvert"
                    input-id="acknowledged_advert"
                    class="mt-0.5 shrink-0"
                />
                <span class="text-sm leading-relaxed text-foreground">{{ $t('trans.registration_ack_advert_label') }}</span>
            </label>
        </fieldset>

        <p v-if="showValidationHint && !acknowledgedAdvert" class="text-sm text-destructive">
            {{ $t('trans.registration_instructions_ack_required') }}
        </p>

        <BaseButton
            type="button"
            :disabled="!acknowledgedAdvert"
            classes="min-h-11 w-full rounded-xl dark:text-white"
            @click="onContinue"
        >
            {{ $t('trans.registration_instructions_continue') }}
        </BaseButton>
    </div>
</template>
