<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { SizeVariant } from '@/enums/sizes';
import { closeModal, getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { useModalStore } from '@/store/core/useModalStore';
import type { HostelApplication } from '@/types/hms';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const emit = defineEmits<{
    declined: [];
}>();

const { updateApplicationStatus } = useHms();
const { modals } = useModalStore();
const hmsStore = useHmsStore();

const application = ref<HostelApplication | null>(null);

const form = useForm({
    declineReason: '',
});

const applicantLabel = computed(() => {
    const attrs = application.value?.attributes;
    if (!attrs) {
        return '';
    }

    return attrs.displayName ?? attrs.studentName ?? attrs.name ?? attrs.studentNumber ?? '';
});

watch(modals!, () => {
    application.value = getModalEdit(APP_MODULE_KEYS.hostel_application_decline) ?? null;
    form.reset();
    form.clearErrors();
});

const onClose = (): void => {
    application.value = null;
    form.reset();
    form.clearErrors();
};

const save = async (): Promise<void> => {
    if (!application.value) {
        return;
    }

    if (!form.declineReason.trim()) {
        form.setError('declineReason', trans('hms.decline_reason_required'));
        return;
    }

    const ok = await updateApplicationStatus(application.value, 'declined', form.declineReason.trim());
    if (ok) {
        hmsStore.refreshApplications();
        closeModal(APP_MODULE_KEYS.hostel_application_decline);
        emit('declined');
        onClose();
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_application_decline"
        :title="$t('hms.decline_application')"
        :size="SizeVariant.sm"
        :action-btn-text="'hms.decline_application'"
        :cancel-btn-text="'trans.cancel'"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="space-y-4">
                <p v-if="applicantLabel" class="text-sm text-muted-foreground">
                    {{ applicantLabel }}
                </p>

                <div class="space-y-2">
                    <Label for="decline-reason">
                        {{ $t('hms.decline_reason') }}
                        <RequiredIndicator />
                    </Label>
                    <Textarea
                        id="decline-reason"
                        v-model="form.declineReason"
                        class="min-h-28"
                        @input="clearFormErrors(form, 'declineReason')"
                    />
                    <InputError :message="form.errors.declineReason" />
                </div>
            </div>
        </template>
    </BaseModal>
</template>
