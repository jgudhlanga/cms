<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { computed, ref, watch } from 'vue';
import { getModalEdit } from '@/lib/alerts';
import { PaymentProofPreview } from '@/types/enrolments';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';

const preview = ref<PaymentProofPreview>();

const { modals } = useModalStore();

watch(modals!, () => {
    preview.value = getModalEdit(APP_MODULE_KEYS.preview_payment_proof);
});

const fileType = computed(() => {
    const url = preview.value?.url ?? '';
    if (!url) return 'unknown';

    const ext = url.split('.').pop()?.toLowerCase();
    if (!ext) return 'unknown';

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'pdf';

    return 'other';
});

</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.preview_payment_proof"
        :title="`${$t('trans.preview')} ${$t('trans.payment_proof')}`"
        :has-form="false"
        :size="SizeVariant.lg"
    >
        <div class="grid grid-cols-1 gap-4">
            <template v-if="preview?.url">
                <!-- Image Preview -->
                <div v-if="fileType === 'image'">
                    <img :src="preview?.url" alt="Preview" class="max-w-sm border rounded" />
                </div>

                <!-- PDF Preview -->
                <div v-else-if="fileType === 'pdf'">
                    <iframe
                        :src="preview?.url"
                        class="w-full h-96 border rounded"
                        frameborder="0"
                    ></iframe>
                </div>

                <!-- Other File -->
                <div v-else>
                    <a :href="preview?.url" target="_blank" class="text-primary underline">
                        Download File
                    </a>
                </div>
            </template>
            <template v-else>
                <BaseAlert :title="$t('no_data')" :description="$t('trans.no_data_found_description', {data: $t('trans.preview')})" />
            </template>
        </div>
    </BaseModal>
</template>
