<script setup lang="ts">
import ApprenticeDetails from '@/components/students/update/ApprenticeDetails.vue';
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import { ColorVariant } from '@/enums/colors';
import { BaseButton } from '@/components/core/button';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    applicationStep?: string;
    applicationTrack?: string;
    applicationTrackLabel?: string;
}

defineProps<Props>();

const submitting = ref(false);
const { redirectIfClosed } = useRegistrationAvailability();

const form = useForm({
    employer: '',
    apprentice_number: '',
});

onMounted(() => {
    redirectIfClosed();
});

const submit = () => {
    submitting.value = true;
    form.post(route('portal.application.apprentice.store'), {
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <PortalApplicationShell>
        <div class="mx-auto flex w-full max-w-2xl flex-col px-5 pb-12">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-semibold text-foreground">
                    {{ $t('trans.application_apprentice_express_title') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t('trans.application_apprentice_express_description') }}
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <ApprenticeDetails :form="form" />

                <div v-if="form.errors.error" class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                    {{ form.errors.error }}
                </div>

                <div class="flex justify-end">
                    <BaseButton
                        type="submit"
                        :variant="ColorVariant.primary"
                        :disabled="submitting || form.processing"
                    >
                        {{ $t('trans.submit') }}
                    </BaseButton>
                </div>
            </form>
        </div>
    </PortalApplicationShell>
</template>
