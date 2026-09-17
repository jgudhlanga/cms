<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import type { StudyPositionBannerItem, StudyPositionState } from '@/types/study-position';
import { trans } from 'laravel-vue-i18n';
import { AlertTriangle, CheckCircle2, ChevronDown, ChevronUp, ClipboardCheck } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        context?: 'portal' | 'admin';
        state: StudyPositionState | null;
        periodLabel: string | null;
        items: StudyPositionBannerItem[];
        canConfirm?: boolean;
        /** Remembers, per viewer, whether the confirmed status line is collapsed. Omit to disable collapsing. */
        storageKey?: string;
    }>(),
    {
        context: 'portal',
        canConfirm: false,
    },
);

const emit = defineEmits<{ confirm: [] }>();

const hasDrift = computed(() => props.items.some((item) => item.drift));
const period = computed(() => props.periodLabel ?? '');

// A confirmed record that no longer matches the phase on file needs the same attention as a review.
const tone = computed<'danger' | 'warning' | 'success' | null>(() => {
    switch (props.state) {
        case 'unconfirmed':
        case 'follow_up':
            return 'danger';
        case 'needs_review':
            return 'warning';
        case 'confirmed':
            if (props.context === 'admin' && hasDrift.value) {
                return 'warning';
            }

            return props.context === 'admin' ? 'success' : null;
        default:
            return null;
    }
});

const isAdmin = computed(() => props.context === 'admin');

const title = computed((): string => {
    const params = { period: period.value };

    if (props.state === 'confirmed') {
        return trans('students.study_position_admin_confirmed_chip', params);
    }

    if (isAdmin.value) {
        return {
            unconfirmed: trans('students.study_position_admin_unconfirmed_title', params),
            follow_up: trans('students.study_position_admin_follow_up_title', params),
            needs_review: trans('students.study_position_admin_review_title', params),
        }[props.state ?? 'unconfirmed'];
    }

    return {
        unconfirmed: trans('students.study_position_banner_unconfirmed_title', params),
        follow_up: trans('students.study_position_banner_follow_up_title', params),
        needs_review: trans('students.study_position_banner_review_title'),
    }[props.state ?? 'unconfirmed'];
});

const body = computed((): string | null => {
    if (props.state === 'confirmed') {
        return null;
    }

    if (isAdmin.value) {
        return props.state === 'unconfirmed' ? trans('students.study_position_admin_unconfirmed_body') : null;
    }

    return {
        unconfirmed: trans('students.study_position_banner_unconfirmed_body'),
        follow_up: trans('students.study_position_banner_follow_up_body'),
        needs_review: trans('students.study_position_banner_review_body', { period: period.value }),
    }[props.state ?? 'unconfirmed'];
});

// Admins see what each programme needs; students see the programmes still waiting on them.
const itemLines = computed((): Array<{ key: string; programme: string; text: string }> => {
    return props.items
        .map((item, index) => {
            let text = '';

            if (isAdmin.value) {
                if (item.drift) {
                    text = trans('students.study_position_admin_drift_body', {
                        confirmed: item.answeredPhase ?? '',
                        system: item.systemPhase ?? '',
                    });
                } else if (item.state === 'follow_up') {
                    text = trans('students.study_position_admin_follow_up_body', { answer: item.answerLabel ?? '' });
                } else if (item.state === 'needs_review') {
                    text = trans('students.study_position_admin_review_body', {
                        phase: item.answeredPhase ?? '',
                        reason: item.syncNote ?? '',
                    });
                } else if (item.state === 'unconfirmed') {
                    text = item.systemPhase ? trans('students.study_position_records_show', { phase: item.systemPhase }) : '';
                } else {
                    text = item.answeredPhase ?? '';
                }
            } else if (item.state !== 'confirmed') {
                text = item.answeredPhase ?? item.systemPhase ?? '';
            }

            return { key: `${index}-${item.programme}`, programme: item.programme, text };
        })
        .filter((line) => line.text !== '' || (isAdmin.value && props.items.length > 1));
});

const actionLabel = computed((): string | null => {
    if (isAdmin.value) {
        return props.canConfirm ? trans('students.study_position_admin_action') : null;
    }

    if (props.state === 'unconfirmed') {
        return trans('students.study_position_banner_confirm_now');
    }

    return props.state === 'follow_up' ? trans('students.study_position_banner_follow_up_action') : null;
});

const toneClasses = computed(() => {
    switch (tone.value) {
        case 'danger':
            return 'border-red-200 bg-red-50 text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100';
        case 'warning':
            return 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100';
        default:
            return 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100';
    }
});

const buttonVariant = computed(() => {
    switch (tone.value) {
        case 'danger':
            return ColorVariant.danger;
        case 'warning':
            return ColorVariant.warning;
        case 'success':
            return ColorVariant.success;
        default:
            return ColorVariant.primary;
    }
});

const icon = computed(() => (tone.value === 'success' ? CheckCircle2 : tone.value === 'warning' ? ClipboardCheck : AlertTriangle));

// A confirmed programme is a status line, not something needing attention — one compact row is enough.
const compact = computed(() => tone.value === 'success');

const compactSummary = computed((): string => itemLines.value.map((line) => `${line.programme}: ${line.text}`).join(' · '));

// Collapsing is a per-viewer convenience, not shared state, so it lives in localStorage only.
const COLLAPSE_STORAGE_PREFIX = 'study-position-banner-collapsed:';

const readCollapsed = (key: string): boolean => {
    try {
        return localStorage.getItem(COLLAPSE_STORAGE_PREFIX + key) === '1';
    } catch {
        return false;
    }
};

const writeCollapsed = (key: string, value: boolean): void => {
    try {
        if (value) {
            localStorage.setItem(COLLAPSE_STORAGE_PREFIX + key, '1');
        } else {
            localStorage.removeItem(COLLAPSE_STORAGE_PREFIX + key);
        }
    } catch {
        // Best-effort convenience only; nothing to fall back to.
    }
};

const canCollapse = computed(() => compact.value && Boolean(props.storageKey));
const collapsed = ref(false);

watch(
    () => props.storageKey,
    (key) => {
        collapsed.value = Boolean(key) && readCollapsed(key!);
    },
    { immediate: true },
);

const toggleCollapsed = (): void => {
    if (!props.storageKey) {
        return;
    }

    collapsed.value = !collapsed.value;
    writeCollapsed(props.storageKey, collapsed.value);
};
</script>

<template>
    <div
        v-if="tone !== null"
        :class="[
            'mb-3 rounded-md border text-sm',
            compact ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-3',
            toneClasses,
        ]"
        :role="tone === 'success' ? 'status' : 'alert'"
    >
        <!-- Confirmed: always a single line. Collapsing (title only) needs a storage key; the edit
             action is an icon, not a full button, since nothing here needs that much weight. -->
        <div v-if="compact" class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <component v-if="!canCollapse" :is="icon" class="size-3.5 shrink-0" />
            <p v-if="!canCollapse" class="font-medium break-words">{{ title }}</p>

            <button
                v-else
                type="button"
                class="flex min-w-0 shrink-0 cursor-pointer items-center gap-1.5"
                :aria-expanded="!collapsed"
                @click="toggleCollapsed"
            >
                <component :is="icon" class="size-3.5 shrink-0" />
                <span class="truncate font-medium">{{ title }}</span>
                <component :is="collapsed ? ChevronDown : ChevronUp" class="size-3.5 shrink-0 opacity-60" />
            </button>

            <span v-if="compactSummary && !collapsed" class="break-words opacity-80">{{ compactSummary }}</span>

            <button
                v-if="actionLabel"
                type="button"
                class="ml-auto inline-flex size-5 shrink-0 items-center justify-center rounded-full text-current hover:bg-black/10 dark:hover:bg-white/10"
                :title="actionLabel"
                :aria-label="actionLabel"
                @click="emit('confirm')"
            >
                <BaseIcon :name="IconName.edit" size="14" class="block" />
            </button>
        </div>

        <div v-if="!compact" class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-start gap-2.5">
                <component :is="icon" class="mt-0.5 size-4 shrink-0" />
                <div class="min-w-0">
                    <p class="font-medium break-words">{{ title }}</p>
                    <p v-if="body" class="mt-1 text-xs opacity-90">{{ body }}</p>
                    <ul v-if="itemLines.length > 0" class="mt-2 flex flex-col gap-1 text-xs">
                        <li v-for="line in itemLines" :key="line.key" class="break-words">
                            <span class="font-semibold">{{ line.programme }}</span>
                            <template v-if="line.text"> — {{ line.text }}</template>
                        </li>
                    </ul>
                </div>
            </div>

            <BaseButton
                v-if="actionLabel"
                type="button"
                :title="actionLabel"
                :variant="buttonVariant"
                :size="ButtonSize.sm"
                classes="shrink-0 rounded-full"
                @click="emit('confirm')"
            />
        </div>
    </div>
</template>
