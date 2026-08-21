<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import RegistrationIntentSummary from '@/components/portal/RegistrationIntentSummary.vue';
import RegistrationStepper from '@/components/portal/RegistrationStepper.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { useRegistrationStepNavigation } from '@/composables/students/useRegistrationStepNavigation';
import type { StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
    transferCollegeName?: string | null;
    requiresFee?: boolean;
    stepperVariant?: string;
};

const props = withDefaults(
    defineProps<{
        collegeName?: string | null;
        intentSummary?: IntentSummary | null;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
        applicationStep?: string;
    }>(),
    {
        collegeName: null,
        intentSummary: null,
        stepperVariant: 'transfer',
        requiresFee: false,
    },
);

const { navigateToRegistrationStep } = useRegistrationStepNavigation();
const submitting = ref(false);

const form = useForm({
    college_name: props.collegeName ?? '',
});

const submit = () => {
    submitting.value = true;
    form.post(route('portal.register.select-college'), {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.application_transfer_college_title')" />
    <div class="min-h-svh bg-background">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 pt-2 sm:p-6 md:pt-6 lg:w-[62%] lg:min-w-0 lg:p-10">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />
                    <RegistrationStepper
                        active-path="zimbabwean"
                        highlighted-step="choose-college"
                        :stepper-variant="stepperVariant"
                        :requires-fee="requiresFee"
                        @navigate="navigateToRegistrationStep"
                    />
                    <RegistrationIntentSummary :summary="intentSummary" />

                    <div class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-md sm:p-8">
                        <div class="mb-5 text-center">
                            <h1 class="text-lg font-semibold text-foreground">
                                {{ $t('trans.application_transfer_college_title') }}
                            </h1>
                            <p class="mt-1.5 text-sm text-muted-foreground">
                                {{ $t('trans.application_transfer_college_description') }}
                            </p>
                        </div>

                        <BaseAlert
                            class="mb-6"
                            :type="TypeVariant.warning"
                            :title="$t('trans.application_transfer_college_accuracy_title')"
                            :description="$t('trans.application_transfer_college_accuracy_banner')"
                        />

                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="space-y-2">
                                <BaseInput
                                    input-id="college_name"
                                    v-model="form.college_name"
                                    :label="$t('trans.application_transfer_college_label')"
                                    :error="form.errors.college_name"
                                    :is-required="true"
                                    :aria-describedby="
                                        form.errors.college_name
                                            ? 'college_name-error college_name-helper'
                                            : 'college_name-helper'
                                    "
                                />
                                <p id="college_name-helper" class="text-xs text-muted-foreground">
                                    {{ $t('trans.application_transfer_college_helper') }}
                                </p>
                            </div>

                            <div
                                v-if="form.errors.track || form.errors.error"
                                class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                            >
                                {{ form.errors.track || form.errors.error }}
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <BaseButton
                                    type="button"
                                    :variant="ColorVariant.shade"
                                    classes="min-h-10 w-full rounded-xl sm:w-auto"
                                    @click="navigateToRegistrationStep('choose-track')"
                                >
                                    {{ $t('trans.back') }}
                                </BaseButton>
                                <BaseButton
                                    type="submit"
                                    :variant="ColorVariant.primary"
                                    :disabled="submitting"
                                    classes="min-h-10 w-full rounded-xl sm:w-auto"
                                >
                                    {{ $t('trans.continue') }}
                                </BaseButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <RegistrationGuide
                active-path="zimbabwean"
                highlighted-step="choose-college"
                :stepper-variant="stepperVariant"
                :requires-fee="requiresFee"
            />
        </div>
    </div>
</template>
