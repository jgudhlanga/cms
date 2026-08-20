<script setup lang="ts">
import { LOGO } from '@/lib/constants';
import { computed } from 'vue';

interface Props {
    studentName: string;
    studentNumber: string;
    department?: string | null;
    level?: string | null;
    course?: string | null;
    mode?: string | null;
    sdp?: string | null;
    residence?: string | null;
    expiryDate?: string | null;
    photoUrl?: string | null;
    logoUrl?: string | null;
    institutionName?: string | null;
    website?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    department: null,
    level: null,
    course: null,
    mode: null,
    sdp: null,
    residence: null,
    expiryDate: null,
    photoUrl: null,
    logoUrl: null,
    institutionName: null,
    website: null,
});

const present = (value: string | null | undefined): string | null => {
    const trimmed = value?.trim() ?? '';

    return trimmed === '' ? null : trimmed;
};

const displayMode = (value: string): string => {
    if (value.toLowerCase() === 'part time') {
        return 'Part-time';
    }

    if (value.toLowerCase() === 'full time') {
        return 'Full-time';
    }

    return value;
};

const displayResidence = (value: string): string => {
    if (value.toLowerCase() === 'non res') {
        return 'Non-Resident';
    }

    if (value.toLowerCase() === 'res') {
        return 'Resident';
    }

    return value;
};

const modeBadge = computed((): string | null => {
    const mode = present(props.mode);

    return mode === null ? null : displayMode(mode);
});

const residenceBadge = computed((): string | null => {
    const residence = present(props.residence);

    return residence === null ? null : displayResidence(residence);
});

const showSdp = computed((): boolean => present(props.sdp)?.toLowerCase() === 'yes');
</script>

<template>
    <div
        class="grid aspect-[85.6/53.98] w-85 max-w-full grid-rows-[2.25rem_minmax(0,1fr)_auto_auto] overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(16,24,50,0.08),0_12px_32px_rgba(16,24,50,0.14)]"
        style="color: #1a2233"
    >
        <div
            class="relative flex items-center justify-between gap-2 bg-linear-to-br from-[#1B3A8C] to-[#0F2760] px-3"
        >
            <div class="flex min-w-0 items-center gap-2">
                <div class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white p-0.5">
                    <img
                        :src="logoUrl || LOGO"
                        alt=""
                        class="size-full object-contain"
                    >
                </div>
                <p class="truncate text-[10px] font-semibold tracking-[0.06em] text-white uppercase">
                    {{ present(institutionName) ?? $t('trans.student_id_card_institution') }}
                </p>
            </div>
            <p class="shrink-0 text-[8px] font-semibold tracking-[0.12em] text-[#D9A441] uppercase">
                {{ $t('trans.student_id_card_title') }}
            </p>
            <div class="absolute inset-x-0 bottom-0 h-0.75 bg-[#D9A441]" />
        </div>

        <div class="flex min-h-0 items-stretch gap-2.5 overflow-hidden px-3 pt-2.5 pb-1.5">
            <div class="flex h-full max-h-full w-auto shrink-0 aspect-35/45 items-center justify-center overflow-hidden rounded-[8px] border-2 border-[#1B3A8C] bg-[#F5F7FC]">
                <img
                    v-if="photoUrl"
                    :src="photoUrl"
                    alt=""
                    class="h-full w-full object-cover object-center"
                >
                <svg
                    v-else
                    class="size-7 opacity-30"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#1B3A8C"
                    stroke-width="1.5"
                    aria-hidden="true"
                >
                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                    />
                    <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7" />
                </svg>
            </div>

            <div class="flex min-h-0 min-w-0 flex-1 flex-col justify-start gap-1 pt-1">
                <div class="min-h-0 shrink">
                    <p class="m-0 text-[7px] font-semibold tracking-[0.09em] text-[#647089] uppercase">
                        {{ $t('trans.ui_full_name') }}
                    </p>
                    <p class="m-0 text-[11px] leading-tight font-semibold wrap-break-word">
                        {{ present(studentName) ?? '—' }}
                    </p>
                </div>
                <div class="min-h-0 shrink">
                    <p class="m-0 text-[7px] font-semibold tracking-[0.09em] text-[#647089] uppercase">
                        {{ $tChoice('trans.department', 1) }}
                    </p>
                    <p class="m-0 text-[9px] leading-tight font-medium wrap-break-word">
                        {{ present(department) ?? '—' }}
                    </p>
                </div>
                <div class="min-h-0 shrink">
                    <p class="m-0 text-[7px] font-semibold tracking-[0.09em] text-[#647089] uppercase">
                        {{ $tChoice('trans.course', 1) }}
                    </p>
                    <p class="m-0 text-[9px] leading-tight font-medium wrap-break-word">
                        {{ present(course) ?? '—' }}
                    </p>
                </div>
                <div
                    v-if="modeBadge || residenceBadge || showSdp"
                    class="mt-0.5 flex shrink-0 flex-wrap gap-1"
                >
                    <span
                        v-if="modeBadge"
                        class="rounded-full bg-[#E7ECF9] px-1.5 py-px text-[7px] font-semibold tracking-wide text-[#1B3A8C]"
                    >
                        {{ modeBadge }}
                    </span>
                    <span
                        v-if="residenceBadge"
                        class="rounded-full bg-[#FBF1DE] px-1.5 py-px text-[7px] font-semibold tracking-wide text-[#8A6417]"
                    >
                        {{ residenceBadge }}
                    </span>
                    <span
                        v-if="showSdp"
                        class="rounded-full bg-[#E7ECF9] px-1.5 py-px text-[7px] font-semibold tracking-wide text-[#1B3A8C]"
                    >
                        {{ $t('trans.student_id_card_sdp') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mx-3 border-t border-[#E4E8F1] pt-1.5">
            <p class="m-0 text-[7px] font-semibold tracking-[0.09em] text-[#647089] uppercase">
                {{ $tChoice('trans.student_number', 1) }}
            </p>
            <p class="m-0 font-mono text-[9px] font-semibold tracking-wide">
                {{ present(studentNumber) ?? '—' }}
            </p>
        </div>

        <div class="flex items-center justify-between px-3 pt-1.5 pb-2">
            <div class="flex flex-col">
                <span class="text-[7px] font-semibold tracking-[0.08em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_valid_until') }}
                </span>
                <span class="text-[9px] font-semibold">
                    {{ present(expiryDate) ?? '—' }}
                </span>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-[7px] font-semibold tracking-[0.08em] text-[#647089] uppercase">
                    {{ $t('trans.student_id_card_website_label') }}
                </span>
                <span class="text-[9px] font-semibold text-[#1B3A8C]">
                    {{ present(website) ?? $t('trans.student_id_card_website') }}
                </span>
            </div>
        </div>
    </div>
</template>
