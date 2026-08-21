<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { BaseButton } from '@/components/core/button';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    applicationStep?: string;
    applicationTrack?: string | null;
    applicationTrackLabel?: string | null;
    collegeName?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    applicationStep: 'transfer-college',
    applicationTrack: null,
    applicationTrackLabel: null,
    collegeName: null,
});

const submitting = ref(false);
const { redirectIfClosed } = useRegistrationAvailability();
const store = useCreateApplicationFormStore();

const form = useForm({
    college_name: props.collegeName ?? store.college_name ?? '',
});

onMounted(() => {
    redirectIfClosed();
});

const submit = () => {
    submitting.value = true;
    store.college_name = form.college_name;

    form.post(route('portal.application.transfer-college.store'), {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <PortalApplicationShell>
        <div class="mx-auto flex w-full max-w-2xl flex-col px-5 pb-12">
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
                            @click="router.visit(route('portal.application.track'))"
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
    </PortalApplicationShell>
</template>
