<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { formatDate } from '@/lib/dates';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import customAxios from '@/services/http-init';
import type { PaymentGatewayDisplay, PaymentGatewayFieldSource } from '@/types/integrations';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useIdle, useTimestamp, watchThrottled } from '@vueuse/core';
import { AxiosError } from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, reactive, ref, watch } from 'vue';
import GatewayField from './partials/GatewayField.vue';
import GatewaySection from './partials/GatewaySection.vue';
import LockOverlay from './partials/LockOverlay.vue';

const IDLE_LOCK_MS = 5 * 60 * 1000;
const IDLE_WARNING_MS = 15_000;
const SESSION_TOUCH_MS = 60_000;

const FIELD_KEYS = [
    'gateway_name',
    'gateway_base_url',
    'gateway_api_key',
    'gateway_secret',
    'bank_statements_base_url',
    'usd_account_number',
    'usd_password',
    'zwg_account_number',
    'zwg_password',
    'income_gen_account_number',
    'income_gen_password',
] as const;

type GatewayFieldKey = (typeof FIELD_KEYS)[number];

type GatewayFieldMeta = {
    key: GatewayFieldKey;
    label: () => string;
    envKey: string;
    secret?: boolean;
};

const checkoutFields: GatewayFieldMeta[] = [
    { key: 'gateway_name', label: () => trans('integrations.gateway_name'), envKey: 'PAYMENT_GATEWAY_NAME' },
    { key: 'gateway_base_url', label: () => trans('integrations.gateway_base_url'), envKey: 'PAYMENT_GATEWAY_BASE_URL' },
    { key: 'gateway_api_key', label: () => trans('integrations.gateway_api_key'), envKey: 'PAYMENT_GATEWAY_API_KEY', secret: true },
    { key: 'gateway_secret', label: () => trans('integrations.gateway_secret'), envKey: 'PAYMENT_GATEWAY_SECRET', secret: true },
];

const statementsUrlField: GatewayFieldMeta = {
    key: 'bank_statements_base_url',
    label: () => trans('integrations.bank_statements_base_url'),
    envKey: 'BANK_STATEMENTS_BASE_URL',
};

const accounts: { title: () => string; number: GatewayFieldMeta; password: GatewayFieldMeta }[] = [
    {
        title: () => trans('integrations.usd_account'),
        number: { key: 'usd_account_number', label: () => trans('integrations.account_number'), envKey: 'ACCOUNT_NUMBER_USD' },
        password: { key: 'usd_password', label: () => trans('trans.password'), envKey: 'ACCOUNT_NUMBER_USD_PASSWORD', secret: true },
    },
    {
        title: () => trans('integrations.zwg_account'),
        number: { key: 'zwg_account_number', label: () => trans('integrations.account_number'), envKey: 'ACCOUNT_NUMBER_ZWG' },
        password: { key: 'zwg_password', label: () => trans('trans.password'), envKey: 'ACCOUNT_NUMBER_ZWG_PASSWORD', secret: true },
    },
    {
        title: () => trans('integrations.income_gen_account'),
        number: {
            key: 'income_gen_account_number',
            label: () => trans('integrations.account_number'),
            envKey: 'ACCOUNT_NUMBER_INCOME_GEN',
        },
        password: {
            key: 'income_gen_password',
            label: () => trans('trans.password'),
            envKey: 'ACCOUNT_NUMBER_INCOME_GEN_PASSWORD',
            secret: true,
        },
    },
];

const props = defineProps<{
    locked: boolean;
    canUpdate: boolean;
    gateway: PaymentGatewayDisplay | null;
}>();

const { open: openConfirm } = useCustomConfirmDialog();
const unlockOverlay = ref<{ setError: (message: string) => void; reset: () => void } | null>(null);
const unlocking = ref(false);
const locking = ref(false);
const isLocked = ref(props.locked);

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.integrations' }, { transKey: 'trans.payment_gateway' }];

const form = useForm<Record<GatewayFieldKey, string>>({
    gateway_name: '',
    gateway_base_url: '',
    gateway_api_key: '',
    gateway_secret: '',
    bank_statements_base_url: '',
    usd_account_number: '',
    usd_password: '',
    zwg_account_number: '',
    zwg_password: '',
    income_gen_account_number: '',
    income_gen_password: '',
});

// Indexing the Inertia form proxy by a dynamic key; the writes still land on the reactive form.
const fields = form as unknown as Record<GatewayFieldKey, string>;
const errors = computed(() => form.errors as Partial<Record<GatewayFieldKey, string>>);

const emptyRecord = <T,>(value: T): Record<GatewayFieldKey, T> =>
    Object.fromEntries(FIELD_KEYS.map((key) => [key, value])) as Record<GatewayFieldKey, T>;

const sources = reactive<Record<GatewayFieldKey, PaymentGatewayFieldSource>>(emptyRecord('missing'));
const secretsSet = reactive<Record<GatewayFieldKey, boolean>>(emptyRecord(false));
/** What the server currently holds, so "changed" and Discard both have something to compare against. */
const baseline = reactive<Record<GatewayFieldKey, string>>(emptyRecord(''));

const isSecret = (key: GatewayFieldKey): boolean => key.endsWith('_key') || key.endsWith('_secret') || key.endsWith('_password');

const applyMeta = (gateway: PaymentGatewayDisplay) => {
    FIELD_KEYS.forEach((key) => {
        const field = gateway[key];
        sources[key] = field.source;
        secretsSet[key] = 'isSet' in field ? field.isSet : false;
    });
};

const applyValues = (gateway: PaymentGatewayDisplay) => {
    FIELD_KEYS.forEach((key) => {
        // Secrets never come back from the server: an empty box means "keep what is stored".
        const value = isSecret(key) ? '' : ((gateway[key] as { value: string }).value ?? '');
        fields[key] = value;
        baseline[key] = value;
    });

    form.clearErrors();
    form.defaults();
};

const changedKeys = computed(() => FIELD_KEYS.filter((key) => fields[key] !== baseline[key]));
const hasChanges = computed(() => changedKeys.value.length > 0);

watch(
    () => props.gateway,
    (gateway) => {
        if (!gateway) {
            return;
        }

        applyMeta(gateway);

        // An unlock reloads this page; keep whatever the user had already typed.
        if (!hasChanges.value) {
            applyValues(gateway);
        }
    },
    { immediate: true },
);

watch(
    () => props.locked,
    (locked) => {
        isLocked.value = locked;
    },
);

const { idle, lastActive } = useIdle(IDLE_LOCK_MS);
const now = useTimestamp({ interval: 1000 });

const warningSeconds = computed(() => {
    if (isLocked.value) {
        return null;
    }

    const remaining = IDLE_LOCK_MS - (now.value - lastActive.value);

    if (remaining <= IDLE_WARNING_MS && remaining > 0) {
        return Math.ceil(remaining / 1000);
    }

    return null;
});

const webClient = customAxios('/');

const lockNow = async () => {
    if (isLocked.value) {
        return;
    }

    locking.value = true;
    isLocked.value = true;
    unlockOverlay.value?.reset();

    try {
        await webClient.post(route('integrations.payment-gateway.lock'));
    } catch {
        // Client lock still covers the form if the session call fails.
    } finally {
        locking.value = false;
    }
};

watch(idle, (becameIdle) => {
    if (becameIdle && !isLocked.value) {
        void lockNow();
    }
});

watch(
    () => (isLocked.value ? 0 : now.value - lastActive.value),
    (idleMs) => {
        if (!isLocked.value && idleMs >= IDLE_LOCK_MS) {
            void lockNow();
        }
    },
);

watchThrottled(
    lastActive,
    () => {
        if (isLocked.value) {
            return;
        }

        void webClient.post(route('integrations.payment-gateway.touch')).catch(() => {
            // Keep the local timer; the next idle lock still covers the page.
        });
    },
    { throttle: SESSION_TOUCH_MS },
);

const leaveWithoutUnlocking = async () => {
    try {
        await webClient.post(route('integrations.payment-gateway.lock'));
    } catch {
        // Still leave the locked page so the user can go elsewhere.
    }

    router.visit(route('dashboard'));
};

const unlock = async (password: string) => {
    unlocking.value = true;

    try {
        await webClient.post(route('integrations.payment-gateway.unlock'), { password });
        unlockOverlay.value?.reset();
        router.reload({
            only: ['gateway', 'locked'],
            preserveScroll: true,
            onFinish: () => {
                unlocking.value = false;
            },
        });
    } catch (error) {
        unlocking.value = false;
        const axiosError = error as AxiosError<{ errors?: { password?: string[] }; message?: string }>;
        const message = axiosError.response?.data?.errors?.password?.[0] ?? axiosError.response?.data?.message ?? trans('auth.password');
        unlockOverlay.value?.setError(message);
    }
};

const discard = () => {
    FIELD_KEYS.forEach((key) => {
        fields[key] = baseline[key];
    });

    form.clearErrors();
};

const save = async () => {
    if (!props.canUpdate || !hasChanges.value) {
        return;
    }

    const confirmed = await openConfirm({
        title: trans('integrations.confirm_title'),
        message: trans('integrations.confirm_message'),
        note: trans('integrations.confirm_note'),
        confirmText: trans('integrations.save'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    form.put(route('integrations.payment-gateway.update'), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.gateway) {
                applyValues(props.gateway);
            }
        },
    });
};

const lastUpdated = computed(() => {
    if (!props.gateway?.updated_at) {
        return trans('integrations.never_updated');
    }

    return trans('integrations.last_updated', { time: formatDate(props.gateway.updated_at, 'll LT') });
});

const summary = computed(() => {
    const counts: Record<PaymentGatewayFieldSource, number> = { database: 0, env: 0, missing: 0 };

    FIELD_KEYS.forEach((key) => {
        counts[sources[key]] += 1;
    });

    return [
        { source: 'database' as const, count: counts.database, label: 'integrations.summary_database', dot: 'bg-emerald-500' },
        { source: 'env' as const, count: counts.env, label: 'integrations.summary_env', dot: 'bg-sky-500' },
        { source: 'missing' as const, count: counts.missing, label: 'integrations.summary_missing', dot: 'bg-amber-500' },
    ];
});

const statusPill = computed(() => {
    if (isLocked.value) {
        return {
            text: trans('integrations.locked_status'),
            chip: 'border-border bg-muted text-muted-foreground',
            dot: 'bg-muted-foreground',
        };
    }

    if (warningSeconds.value !== null) {
        return {
            text: trans('integrations.idle_warning', { seconds: String(warningSeconds.value) }),
            chip: 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-400',
            dot: 'bg-amber-500 animate-pulse',
        };
    }

    return {
        text: trans('integrations.unlocked_status'),
        chip: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    };
});

const fieldProps = (meta: GatewayFieldMeta) => ({
    inputId: meta.key,
    label: meta.label(),
    envKey: meta.envKey,
    source: sources[meta.key],
    error: errors.value[meta.key],
    secret: meta.secret ?? false,
    isSet: secretsSet[meta.key],
});
</script>

<template>
    <Head :title="$t('trans.payment_gateway')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="relative mx-auto w-full max-w-5xl space-y-3 px-2 sm:px-4">
            <!-- Identity, freshness and lock state in one row: the three things you check before editing. -->
            <header class="border-border bg-card flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3">
                <span class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                    <component :is="icons[IconName.unplug]" class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="text-accent-foreground text-xs font-bold uppercase">{{ $t('integrations.title') }}</h1>
                    <p class="text-muted-foreground truncate text-xs">
                        {{ $t('integrations.description') }}
                        <span class="text-muted-foreground/70">· {{ lastUpdated }}</span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span
                        v-if="!canUpdate"
                        class="border-border text-muted-foreground inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                        :title="$t('integrations.view_only_hint')"
                    >
                        <component :is="icons[IconName.eye]" class="size-3" aria-hidden="true" />
                        {{ $t('integrations.view_only') }}
                    </span>
                    <span
                        :class="
                            cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold tabular-nums',
                                statusPill.chip,
                            )
                        "
                    >
                        <span :class="cn('size-1.5 rounded-full', statusPill.dot)" aria-hidden="true" />
                        {{ statusPill.text }}
                    </span>
                    <BaseButton
                        v-if="!isLocked"
                        type="button"
                        :variant="ColorVariant.shade_outline"
                        :size="ButtonSize.sm"
                        :processing="locking"
                        @click="lockNow"
                    >
                        <component :is="icons[IconName.lock]" class="size-3.5" aria-hidden="true" />
                        {{ $t('integrations.lock_now') }}
                    </BaseButton>
                </div>
            </header>

            <p v-if="warningSeconds !== null" class="sr-only" aria-live="assertive">{{ $t('integrations.about_to_lock') }}</p>

            <!-- Counts double as the legend for the chips on each field. -->
            <div
                v-if="gateway"
                role="status"
                class="border-border bg-muted/40 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border px-3 py-2 text-xs"
            >
                <span v-for="item in summary" :key="item.source" class="text-muted-foreground inline-flex items-center gap-1.5">
                    <span :class="cn('size-1.5 rounded-full', item.dot)" aria-hidden="true" />
                    <span :class="item.source === 'missing' && item.count > 0 ? 'font-semibold text-amber-600 dark:text-amber-400' : ''">
                        {{ $t(item.label, { count: String(item.count) }) }}
                    </span>
                </span>
                <span class="text-muted-foreground/80 ml-auto hidden sm:block">{{ $t('integrations.fallback_note') }}</span>
            </div>

            <form @submit.prevent="save">
                <fieldset :disabled="isLocked || !canUpdate" class="space-y-3">
                    <GatewaySection
                        :icon="IconName.wallet_cards"
                        :title="$t('integrations.online_checkout')"
                        :description="$t('integrations.online_checkout_description')"
                    >
                        <div class="grid gap-x-4 gap-y-5 md:grid-cols-2">
                            <GatewayField v-for="field in checkoutFields" :key="field.key" v-model="fields[field.key]" v-bind="fieldProps(field)" />
                        </div>
                    </GatewaySection>

                    <GatewaySection
                        :icon="IconName.landmark"
                        :title="$t('integrations.bank_statements')"
                        :description="$t('integrations.bank_statements_description')"
                    >
                        <div class="space-y-4">
                            <div class="grid gap-x-4 gap-y-5 md:grid-cols-2">
                                <GatewayField v-model="fields[statementsUrlField.key]" v-bind="fieldProps(statementsUrlField)" />
                            </div>

                            <div class="grid gap-3 lg:grid-cols-3">
                                <div
                                    v-for="account in accounts"
                                    :key="account.number.key"
                                    class="border-border/70 bg-muted/30 space-y-5 rounded-lg border p-3"
                                >
                                    <p class="text-muted-foreground text-[11px] font-semibold tracking-wide uppercase">
                                        {{ account.title() }}
                                    </p>
                                    <GatewayField v-model="fields[account.number.key]" v-bind="fieldProps(account.number)" />
                                    <GatewayField v-model="fields[account.password.key]" v-bind="fieldProps(account.password)" />
                                </div>
                            </div>
                        </div>
                    </GatewaySection>
                </fieldset>

                <!-- Only shows once something actually changed, and says what is pending. -->
                <div v-if="canUpdate && hasChanges" class="sticky bottom-3 z-20 mt-3">
                    <div
                        class="border-border bg-background/95 flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-2.5 shadow-lg backdrop-blur"
                    >
                        <p class="text-foreground inline-flex items-center gap-2 text-xs font-semibold">
                            <span class="size-1.5 rounded-full bg-amber-500" aria-hidden="true" />
                            {{ $tChoice('integrations.unsaved_changes', changedKeys.length, { count: String(changedKeys.length) }) }}
                        </p>
                        <div class="flex items-center gap-2">
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.shade_outline"
                                :size="ButtonSize.sm"
                                :disabled="form.processing || isLocked"
                                @click="discard"
                            >
                                {{ $t('integrations.discard') }}
                            </BaseButton>
                            <BaseButton
                                type="submit"
                                :variant="ColorVariant.primary"
                                :size="ButtonSize.sm"
                                :processing="form.processing"
                                :disabled="form.processing || isLocked"
                            >
                                {{ $t('integrations.save') }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </form>

            <LockOverlay v-if="isLocked" ref="unlockOverlay" :processing="unlocking" @unlock="unlock" @close="leaveWithoutUnlocking" />
        </div>
    </PageContainer>
</template>
