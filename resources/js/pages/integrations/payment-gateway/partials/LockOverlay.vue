<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { PasswordInputWithToggle } from '@/components/core/form';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { clearFormErrors } from '@/lib/forms';
import { icons } from '@/lib/icons';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

const props = defineProps<{
    processing?: boolean;
}>();

const emit = defineEmits<{
    unlock: [password: string];
    close: [];
}>();

const overlay = ref<HTMLElement | null>(null);
const form = useForm({
    password: '',
});

const submit = () => {
    emit('unlock', form.password);
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
    setError(message: string) {
        form.setError('password', message);
    },
    reset() {
        form.reset();
        form.clearErrors();
    },
});
</script>

<template>
    <div
        ref="overlay"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="payment-gateway-lock-title"
        @keydown="trapFocus"
    >
        <form class="border-border bg-background relative w-full max-w-sm space-y-5 rounded-2xl border p-5 shadow-2xl" @submit.prevent="submit">
            <button
                type="button"
                class="text-muted-foreground hover:bg-accent hover:text-foreground absolute top-3 right-3 rounded-full p-2"
                :aria-label="trans('trans.close')"
                :disabled="props.processing"
                @click="close"
            >
                <component :is="icons[IconName.close]" class="size-4" aria-hidden="true" />
            </button>

            <div class="flex flex-col items-center gap-2 text-center">
                <span class="bg-primary/10 text-primary flex size-11 items-center justify-center rounded-full">
                    <component :is="icons[IconName.lock]" class="size-5" aria-hidden="true" />
                </span>
                <h2 id="payment-gateway-lock-title" class="text-base font-semibold tracking-tight">
                    {{ trans('integrations.locked_title') }}
                </h2>
                <p class="text-muted-foreground text-xs leading-relaxed">
                    {{ trans('integrations.locked_description') }}
                </p>
            </div>

            <PasswordInputWithToggle
                v-model="form.password"
                :input-auto-focus="true"
                :error="form.errors.password"
                :label="trans('trans.password')"
                :placeholder="trans('trans.password')"
                :is-required="true"
                autocomplete="current-password"
                @input="clearFormErrors(form, 'password')"
            />

            <div class="space-y-2">
                <BaseButton
                    :variant="ColorVariant.primary"
                    type="submit"
                    :processing="props.processing"
                    :disabled="props.processing"
                    classes="min-h-11 w-full rounded-xl"
                >
                    {{ trans('integrations.unlock') }}
                </BaseButton>
                <BaseButton
                    :variant="ColorVariant.shade_outline"
                    type="button"
                    :disabled="props.processing"
                    classes="min-h-11 w-full rounded-xl"
                    @click="close"
                >
                    {{ trans('trans.close') }}
                </BaseButton>
                <!-- Said up front so the later countdown is expected, not a surprise. -->
                <p class="text-muted-foreground/80 pt-1 text-center text-[11px]">
                    {{ trans('integrations.locked_hint') }}
                </p>
            </div>
        </form>
    </div>
</template>
