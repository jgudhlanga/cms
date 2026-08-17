<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { TextFieldType } from '@/enums/inputs';
import { closeModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import IdCardPreviewStack from '@/pages/portal/student/id-card/partials/IdCardPreviewStack.vue';
import type { StudentIdCardSettings } from '@/types/id-cards';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface Props {
    idCardSettings: StudentIdCardSettings;
}

const props = defineProps<Props>();

const form = useForm({
    institution_name: props.idCardSettings.institutionName,
    website: props.idCardSettings.website ?? '',
    return_name: props.idCardSettings.returnName,
    return_address: props.idCardSettings.returnAddress,
    return_phone: props.idCardSettings.returnPhone ?? '',
    logo: null as File | null,
    principal_signature: null as File | null,
});

const logoObjectUrl = ref<string | null>(null);
const signatureObjectUrl = ref<string | null>(null);

const logoPreview = computed(() => logoObjectUrl.value ?? props.idCardSettings.logoUrl);
const signaturePreview = computed(() => signatureObjectUrl.value ?? props.idCardSettings.signatureUrl);

watch(
    () => props.idCardSettings,
    (settings) => {
        form.institution_name = settings.institutionName;
        form.website = settings.website ?? '';
        form.return_name = settings.returnName;
        form.return_address = settings.returnAddress;
        form.return_phone = settings.returnPhone ?? '';
        form.logo = null;
        form.principal_signature = null;
        form.clearErrors();
        if (logoObjectUrl.value) {
            URL.revokeObjectURL(logoObjectUrl.value);
            logoObjectUrl.value = null;
        }
        if (signatureObjectUrl.value) {
            URL.revokeObjectURL(signatureObjectUrl.value);
            signatureObjectUrl.value = null;
        }
    },
);

onBeforeUnmount(() => {
    if (logoObjectUrl.value) {
        URL.revokeObjectURL(logoObjectUrl.value);
    }
    if (signatureObjectUrl.value) {
        URL.revokeObjectURL(signatureObjectUrl.value);
    }
});

const handleLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.logo = input.files?.[0] ?? null;
    if (logoObjectUrl.value) {
        URL.revokeObjectURL(logoObjectUrl.value);
    }
    logoObjectUrl.value = form.logo instanceof File ? URL.createObjectURL(form.logo) : null;
};

const handleSignatureChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.principal_signature = input.files?.[0] ?? null;
    if (signatureObjectUrl.value) {
        URL.revokeObjectURL(signatureObjectUrl.value);
    }
    signatureObjectUrl.value = form.principal_signature instanceof File
        ? URL.createObjectURL(form.principal_signature)
        : null;
};

const save = () => {
    form.post(route('admin.students.id-card-requests.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeModal(APP_MODULE_KEYS.student_id_card_settings),
    });
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_id_card_settings"
        :title="$t('trans.settings')"
        :size="SizeVariant.full"
        :action-btn-text="'trans.save'"
        :on-form-action="save"
        :form="form"
    >
        <template #body>
            <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="space-y-5">
                    <p class="text-sm text-muted-foreground">
                        {{ $t('trans.student_id_card_settings_help') }}
                    </p>
                    <BaseInput
                        input-id="id_card_institution_name"
                        v-model="form.institution_name"
                        :label="$t('trans.student_id_card_institution_name')"
                        :label-uppercase="true"
                        :is-required="true"
                        :error="form.errors.institution_name"
                    />
                    <BaseInput
                        input-id="id_card_website"
                        v-model="form.website"
                        :label="$t('trans.student_id_card_website_label')"
                        :label-uppercase="true"
                        :error="form.errors.website"
                    />
                    <BaseInput
                        input-id="id_card_return_name"
                        v-model="form.return_name"
                        :label="$t('trans.student_id_card_return_name')"
                        :label-uppercase="true"
                        :is-required="true"
                        :error="form.errors.return_name"
                    />
                    <BaseInput
                        input-id="id_card_return_address"
                        v-model="form.return_address"
                        :label="$t('trans.student_id_card_return_address')"
                        :label-uppercase="true"
                        :is-required="true"
                        :error="form.errors.return_address"
                    />
                    <BaseInput
                        input-id="id_card_return_phone"
                        v-model="form.return_phone"
                        :label="$t('trans.student_id_card_return_phone')"
                        :label-uppercase="true"
                        :error="form.errors.return_phone"
                    />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <BaseInput
                                input-id="id_card_logo"
                                :label="$t('trans.logo')"
                                :label-uppercase="true"
                                :type="TextFieldType.file"
                                :error="form.errors.logo"
                                accept="image/jpeg,image/png"
                                @change="handleLogoChange"
                            />
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                alt=""
                                class="h-20 w-20 rounded-full object-contain bg-muted p-1"
                            >
                        </div>
                        <div class="space-y-2">
                            <BaseInput
                                input-id="id_card_principal_signature"
                                :label="$t('trans.student_id_card_principal_signature')"
                                :label-uppercase="true"
                                :type="TextFieldType.file"
                                :error="form.errors.principal_signature"
                                accept="image/jpeg,image/png"
                                @change="handleSignatureChange"
                            />
                            <img
                                v-if="signaturePreview"
                                :src="signaturePreview"
                                alt=""
                                class="h-16 max-w-40 object-contain"
                            >
                        </div>
                    </div>
                </div>
                <aside class="w-full min-w-0 lg:sticky lg:top-0 lg:w-85">
                    <IdCardPreviewStack
                        student-name="Jane Example"
                        student-number="H123456"
                        department="Information Science"
                        course="Information Technology"
                        mode="Full time"
                        residence="NON Res"
                        expiry-date="31 Dec 2026"
                        serial-number="HPC-H123456-1"
                        national-id="63-123456-A-63"
                        :return-name="form.return_name"
                        :return-address="form.return_address"
                        :return-phone="form.return_phone"
                        :logo-url="logoPreview"
                        :institution-name="form.institution_name"
                        :website="form.website"
                        :signature-url="signaturePreview"
                    />
                </aside>
            </div>
        </template>
    </BaseModal>
</template>
