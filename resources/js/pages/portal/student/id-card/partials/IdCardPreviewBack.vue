<script setup lang="ts">
import { LOGO } from '@/lib/constants';
import QRCode from 'qrcode';
import { computed, ref, watch } from 'vue';

interface Props {
    studentNumber?: string | null;
    serialNumber?: string | null;
    nationalId?: string | null;
    returnName?: string | null;
    returnAddress?: string | null;
    returnPhone?: string | null;
    logoUrl?: string | null;
    institutionName?: string | null;
    signatureUrl?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    studentNumber: null,
    serialNumber: null,
    nationalId: null,
    returnName: null,
    returnAddress: null,
    returnPhone: null,
    logoUrl: null,
    institutionName: null,
    signatureUrl: null,
});

const qrDataUrl = ref<string | null>(null);

const present = (value: string | null | undefined): string | null => {
    const trimmed = value?.trim() ?? '';

    return trimmed === '' ? null : trimmed;
};

const qrPayload = computed((): string | null => {
    const studentNumber = present(props.studentNumber);
    const serialNumber = present(props.serialNumber);

    if (studentNumber !== null && serialNumber !== null) {
        return `${studentNumber}|${serialNumber}`;
    }

    return studentNumber ?? serialNumber;
});

watch(
    qrPayload,
    async (payload) => {
        if (payload === null) {
            qrDataUrl.value = null;

            return;
        }

        try {
            qrDataUrl.value = await QRCode.toDataURL(payload, {
                margin: 0,
                width: 96,
                color: {
                    dark: '#1A2233',
                    light: '#FFFFFF',
                },
            });
        } catch {
            qrDataUrl.value = null;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="grid aspect-[85.6/53.98] w-85 max-w-full grid-rows-[2.25rem_minmax(0,1fr)_auto] overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(16,24,50,0.08),0_12px_32px_rgba(16,24,50,0.14)]"
        style="color: #1a2233"
    >
        <div
            class="relative flex items-center gap-2 bg-[#1A2233] px-3"
        >
            <div class="flex min-w-0 items-center gap-2">
                <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white p-0.5">
                    <img
                        :src="logoUrl || LOGO"
                        alt=""
                        class="size-full object-contain grayscale"
                    >
                </div>
                <p class="truncate text-[10px] font-semibold tracking-[0.06em] text-white uppercase">
                    {{ present(institutionName) ?? $t('trans.student_id_card_institution') }}
                </p>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-0.75 bg-[#9AA3B5]" />
        </div>

        <div class="flex min-h-0 flex-col gap-1.5 overflow-hidden px-3 pt-2">
            <div>
                <p class="m-0 text-[7px] font-semibold tracking-[0.06em] text-[#1A2233] uppercase">
                    {{ $t('trans.student_id_card_terms_title') }}
                </p>
                <p class="m-0 mt-0.5 text-[6.5px] leading-snug text-[#647089]">
                    {{ $t('trans.student_id_card_terms') }}
                </p>
            </div>
            <div>
                <p class="m-0 text-[7px] font-semibold tracking-[0.09em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_national_id') }}
                </p>
                <p class="m-0 font-mono text-[9px] font-semibold tracking-wide">
                    {{ present(nationalId) ?? '—' }}
                </p>
            </div>
            <div class="mt-auto w-1/2 pt-1">
                <img
                    v-if="signatureUrl"
                    :src="signatureUrl"
                    alt=""
                    class="h-4 max-w-full object-contain object-left"
                >
                <div
                    v-else
                    class="h-4 border-b border-[#E4E8F1]"
                />
                <p class="m-0 mt-0.5 text-[6.5px] font-semibold tracking-[0.08em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_principal_signature') }}
                </p>
            </div>
        </div>

        <div class="flex items-end justify-between gap-2 px-3 pt-1.5 pb-2">
            <div class="min-w-0">
                <p class="m-0 text-[6.5px] font-semibold tracking-[0.08em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_if_lost') }}
                </p>
                <p class="m-0 text-[8px] font-semibold">
                    {{ present(returnName) ?? present(institutionName) ?? $t('trans.student_id_card_institution') }}
                </p>
                <p
                    v-if="present(returnAddress)"
                    class="m-0 text-[7px] leading-tight text-[#647089]"
                >
                    {{ returnAddress }}
                </p>
                <p
                    v-if="present(returnPhone)"
                    class="m-0 text-[7px] text-[#647089]"
                >
                    Tel: {{ returnPhone }}
                </p>
            </div>
            <div class="flex shrink-0 flex-col items-center">
                <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt=""
                    class="size-10 grayscale"
                >
                <div
                    v-else
                    class="size-10 bg-[#F3F4F6]"
                />
                <p class="m-0 mt-0.5 text-[6px] font-semibold tracking-[0.06em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_scan_to_verify') }}
                </p>
            </div>
        </div>
    </div>
</template>
