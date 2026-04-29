<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { TextFieldType } from '@/enums/inputs';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { StudentProgram } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseInput from '../../core/form/text/BaseInput.vue';

interface Props {
    application: StudentProgram;
    type: 'application_fee' | 'tuition_fee' | 'other';
}

const props = defineProps<Props>();

const { uploadProofOfPayment } = useStudentApplications();

const form = useForm<any>({
    proof_of_payment: '',
    type: props.type,
});

const uploadPreview = ref<string | null>(null);
const fileType = ref<string | null>(null);

const handleFileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    form.proof_of_payment = upload;

    // Clear old preview URL
    if (uploadPreview.value) {
        URL.revokeObjectURL(uploadPreview.value);
    }

    fileType.value = upload.type;

    // Only create object URL for previewable types
    if (upload.type.startsWith('image/') || upload.type === 'application/pdf') {
        uploadPreview.value = URL.createObjectURL(upload);
    } else {
        uploadPreview.value = null;
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.upload_proof_of_payment"
        :title="$t('trans.upload_proof_of_payment')"
        :on-form-action="() => uploadProofOfPayment(form, application)"
        :form="form"
        :size="SizeVariant.md"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4">
                <BaseInput
                    input-id="proof_of_payment"
                    :error="form.errors.proof_of_payment"
                    :label="$tChoice('trans.file', 1)"
                    :type="TextFieldType.file"
                    @change="handleFileChange"
                />
                <!-- IMAGE PREVIEW -->
                <img v-if="uploadPreview && fileType?.startsWith('image/')" class="w-30" :src="uploadPreview" :alt="$t('trans.ui_image_preview')" />
                <!-- PDF PREVIEW -->
                <iframe v-else-if="uploadPreview && fileType === 'application/pdf'" :src="uploadPreview" class="h-64 w-full border"></iframe>
                <!-- GENERIC FILE PREVIEW -->
                <div v-else-if="form.proof_of_payment" class="flex items-center gap-2 rounded border p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V7l-5-5H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        />
                    </svg>
                    <span>{{ form.proof_of_payment.name }}</span>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
