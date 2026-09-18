<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox, BaseInput, BaseSelect, PasswordInputWithToggle } from '@/components/core/form';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import type { ConsoleCommandDefinition, ConsoleCommandParam } from '@/types/console';
import type { RadioGroupOption } from '@/types/forms';
import type { SelectOption } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    command: ConsoleCommandDefinition;
    processing?: boolean;
}>();

const emit = defineEmits<{
    submit: [payload: { parameters: Record<string, unknown>; password: string }];
    close: [];
}>();

const overlay = ref<HTMLElement | null>(null);
const password = ref('');
const confirmation = ref('');
const parameters = reactive<Record<string, unknown>>({});
const errors = reactive<Record<string, string>>({});

const allParams = computed(() => [...props.command.arguments, ...props.command.options]);

const isDestructive = computed(() => props.command.risk === 'destructive');

const riskLabel = computed(() => {
    if (props.command.risk === 'destructive') {
        return trans('console.risk_destructive');
    }

    if (props.command.risk === 'mutating') {
        return trans('console.risk_mutating');
    }

    return trans('console.risk_read_only');
});

const riskClass = computed(() => {
    if (props.command.risk === 'destructive') {
        return 'border-red-500/40 bg-red-500/10 text-red-600';
    }

    if (props.command.risk === 'mutating') {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-700';
    }

    return 'border-border bg-muted text-muted-foreground';
});

const hydrateParameters = () => {
    Object.keys(parameters).forEach((key) => {
        delete parameters[key];
    });

    allParams.value.forEach((param) => {
        parameters[param.name] = param.default ?? (param.type === 'boolean' ? false : '');
    });
};

hydrateParameters();

watch(
    () => props.command.key,
    () => {
        password.value = '';
        confirmation.value = '';
        Object.keys(errors).forEach((key) => {
            delete errors[key];
        });
        hydrateParameters();
    },
);

const choiceLabel = (param: ConsoleCommandParam, choice: string): string => {
    if (choice === '--dry-run') {
        return trans('console.choice_dry_run');
    }

    if (choice === '--execute') {
        return trans('console.choice_execute');
    }

    if (choice === 'usd') {
        return trans('console.account_usd');
    }

    if (choice === 'zwg') {
        return trans('console.account_zwg');
    }

    if (choice === 'income-gen') {
        return trans('console.account_income_gen');
    }

    return choice;
};

const selectOptions = (param: ConsoleCommandParam): SelectOption[] => {
    return param.choices.map((choice) => ({
        label: choiceLabel(param, choice),
        value: choice,
    }));
};

const radioOptions = (param: ConsoleCommandParam): RadioGroupOption[] => {
    return param.choices.map((choice) => ({
        inputId: `${props.command.key}-${param.name}-${choice}`,
        label: choiceLabel(param, choice),
        value: choice,
    }));
};

const commandLine = computed(() => {
    const parts = ['php artisan', props.command.signature];

    allParams.value.forEach((param) => {
        const value = parameters[param.name];

        if (value === null || value === '' || value === undefined) {
            return;
        }

        if (param.type === 'boolean') {
            if (value === true) {
                parts.push(param.name);
            }

            return;
        }

        if (param.type === 'radio') {
            parts.push(String(value));

            return;
        }

        if (param.name.startsWith('--')) {
            parts.push(`${param.name}=${value}`);

            return;
        }

        parts.push(String(value));
    });

    parts.push('--no-interaction');

    return parts.join(' ');
});

const confirmationMatches = computed(() => {
    if (!isDestructive.value) {
        return true;
    }

    return confirmation.value.trim() === props.command.signature;
});

const canSubmit = computed(() => password.value !== '' && confirmationMatches.value && !props.processing);

const paramError = (name: string): string | undefined => {
    return errors[`parameters.${name}`] ?? errors[name];
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }

    emit('submit', {
        password: password.value,
        parameters: { ...parameters },
    });
};

const close = () => {
    if (props.processing) {
        return;
    }

    emit('close');
};

const trapFocus = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
    }

    if (event.key !== 'Tab' || !overlay.value) {
        return;
    }

    const focusable = Array.from(
        overlay.value.querySelectorAll<HTMLElement>(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    );

    if (focusable.length === 0) {
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
};

defineExpose({
    setError(field: string, message: string) {
        errors[field] = message;
    },
    reset() {
        password.value = '';
        confirmation.value = '';
        Object.keys(errors).forEach((key) => {
            delete errors[key];
        });
        hydrateParameters();
    },
});
</script>

<template>
    <div
        ref="overlay"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`console-run-${command.key}`"
        @keydown="trapFocus"
    >
        <form
            class="border-border bg-background relative max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-2xl border p-5 shadow-2xl"
            @submit.prevent="submit"
        >
            <button
                type="button"
                class="text-muted-foreground hover:bg-accent hover:text-foreground absolute top-3 right-3 rounded-full p-2"
                :aria-label="trans('trans.close')"
                :disabled="processing"
                @click="close"
            >
                <component :is="icons[IconName.close]" class="size-4" aria-hidden="true" />
            </button>

            <div class="space-y-2 pr-8">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 :id="`console-run-${command.key}`" class="text-base font-semibold tracking-tight">
                        {{ command.label }}
                    </h2>
                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold uppercase" :class="riskClass">
                        {{ riskLabel }}
                    </span>
                </div>
                <p class="font-mono text-[11px] text-muted-foreground">{{ command.signature }}</p>
                <p class="text-muted-foreground text-xs leading-relaxed">{{ command.details }}</p>
            </div>

            <div v-if="allParams.length > 0" class="space-y-3">
                <template v-for="param in allParams" :key="param.name">
                    <BaseCheckbox
                        v-if="param.type === 'boolean'"
                        :input-id="`${command.key}-${param.name}`"
                        v-model="parameters[param.name]"
                        :label="param.label"
                    />
                    <BaseRadioGroup
                        v-else-if="param.type === 'radio'"
                        v-model="parameters[param.name]"
                        :label="param.label"
                        :options="radioOptions(param)"
                        :is-required="param.required"
                        :error="paramError(param.name)"
                    />
                    <BaseSelect
                        v-else-if="param.type === 'select' || param.type === 'file'"
                        v-model="parameters[param.name]"
                        :label="param.label"
                        :options="selectOptions(param)"
                        :is-required="param.required"
                        :is-clearable="!param.required"
                        :error="paramError(param.name)"
                    />
                    <BaseInput
                        v-else-if="param.type === 'integer'"
                        :input-id="`${command.key}-${param.name}`"
                        v-model="parameters[param.name]"
                        :label="param.label"
                        :type="TextFieldType.number"
                        :is-required="param.required"
                        :error="paramError(param.name)"
                    />
                    <div v-else-if="param.type === 'date'" class="flex flex-col space-y-2">
                        <label class="font-medium text-sm" :for="`${command.key}-${param.name}`">
                            {{ param.label }}
                            <span v-if="param.required" class="text-destructive">*</span>
                        </label>
                        <input
                            :id="`${command.key}-${param.name}`"
                            v-model="parameters[param.name]"
                            type="date"
                            class="border-input bg-background text-foreground placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border p-3 focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <p v-if="paramError(param.name)" class="text-destructive text-xs">{{ paramError(param.name) }}</p>
                    </div>
                    <BaseInput
                        v-else
                        :input-id="`${command.key}-${param.name}`"
                        v-model="parameters[param.name]"
                        :label="param.label"
                        :is-required="param.required"
                        :error="paramError(param.name)"
                    />
                    <p v-if="param.hint" class="text-muted-foreground -mt-2 text-[11px] leading-relaxed">{{ param.hint }}</p>
                </template>
            </div>

            <div class="border-border bg-muted/40 rounded-lg border px-3 py-2">
                <p class="text-muted-foreground text-[11px] font-semibold uppercase">{{ trans('console.command_line') }}</p>
                <p class="mt-1 font-mono text-[11px] break-all">{{ commandLine }}</p>
            </div>

            <PasswordInputWithToggle
                v-model="password"
                :input-auto-focus="allParams.length === 0"
                :error="errors.password"
                :label="trans('console.password')"
                :placeholder="trans('trans.password')"
                :is-required="true"
                autocomplete="current-password"
                @input="delete errors.password"
            />
            <p class="text-muted-foreground -mt-2 text-[11px]">{{ trans('console.password_hint') }}</p>

            <BaseInput
                v-if="isDestructive"
                input-id="console-confirm-signature"
                v-model="confirmation"
                :label="trans('console.confirm_signature')"
                :is-required="true"
                :error="errors.confirmation"
                autocomplete="off"
            />
            <p v-if="isDestructive" class="text-muted-foreground -mt-2 text-[11px]">
                {{ trans('console.confirm_signature_hint', { command: command.signature }) }}
            </p>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                <BaseButton :variant="ColorVariant.shade_outline" type="button" :disabled="processing" @click="close">
                    {{ trans('console.cancel') }}
                </BaseButton>
                <BaseButton
                    :variant="isDestructive ? ColorVariant.danger : ColorVariant.primary"
                    type="submit"
                    :processing="processing"
                    :disabled="!canSubmit"
                >
                    {{ trans('console.queue') }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>
