<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import { BaseButton } from '@/components/core/button';
import { EmailInputWithIcon, PasswordInputWithToggle } from '@/components/core/form';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm<any>({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.ui_reset_password')" />
    <form @submit.prevent="submit" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.ui_reset_password')" :subtitle="$t('trans.reset_password_subtitle')">
            <div class="space-y-4">
                <EmailInputWithIcon
                    v-model="form.email"
                    :error="form.errors.email"
                    :label="$t('trans.email')"
                    :placeholder="$t('trans.email')"
                    :is-required="true"
                    readonly
                />
                <PasswordInputWithToggle
                    v-model="form.password"
                    input-id="password"
                    :input-auto-focus="true"
                    :tabindex="1"
                    :error="form.errors.password"
                    :label="$t('trans.password')"
                    :placeholder="$t('trans.password')"
                    :is-required="true"
                    autocomplete="new-password"
                    @input="clearFormErrors(form, 'password')"
                />
                <PasswordInputWithToggle
                    v-model="form.password_confirmation"
                    input-id="password_confirmation"
                    :tabindex="2"
                    :error="form.errors.password_confirmation"
                    :label="$t('trans.confirm_password')"
                    :placeholder="$t('trans.ui_confirm_password_2')"
                    :is-required="true"
                    autocomplete="new-password"
                    @input="clearFormErrors(form, 'password_confirmation')"
                />
            </div>

            <BaseButton
                :variant="ColorVariant.primary"
                type="submit"
                :tabindex="3"
                :processing="form.processing"
                classes="min-h-11 w-full rounded-xl dark:text-white"
            >
                {{ $t('trans.ui_reset_password') }}
            </BaseButton>
        </AuthCard>
    </form>
</template>
