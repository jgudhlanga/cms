<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import { BaseButton } from '@/components/core/button';
import { BaseInput, EmailInputWithIcon, PasswordInputWithToggle } from '@/components/core/form';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm<any>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="$t('trans.ui_register')" />
    <form @submit.prevent="submit" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.ui_register')" :subtitle="$t('trans.register_subtitle')">
            <div class="space-y-4">
                <BaseInput
                    v-model="form.name"
                    input-id="name"
                    :input-auto-focus="true"
                    :error="form.errors.name"
                    :label="$tChoice('trans.name', 1)"
                    :placeholder="$t('trans.ui_full_name')"
                    :is-required="true"
                    classes="min-h-11 rounded-xl bg-background/80"
                    autocomplete="name"
                    @update:model-value="clearFormErrors(form, 'name')"
                />
                <EmailInputWithIcon
                    v-model="form.email"
                    :error="form.errors.email"
                    :label="$t('trans.email')"
                    :placeholder="$t('trans.email')"
                    :is-required="true"
                    @input="clearFormErrors(form, 'email')"
                />
                <PasswordInputWithToggle
                    v-model="form.password"
                    input-id="password"
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
                    :error="form.errors.password_confirmation"
                    :label="$t('trans.ui_confirm_password_2')"
                    :placeholder="$t('trans.ui_confirm_password_2')"
                    :is-required="true"
                    autocomplete="new-password"
                    @input="clearFormErrors(form, 'password_confirmation')"
                />
            </div>

            <BaseButton
                :variant="ColorVariant.primary"
                type="submit"
                :processing="form.processing"
                classes="min-h-11 w-full rounded-xl dark:text-white"
            >
                {{ $t('trans.ui_create_account') }}
            </BaseButton>

            <div class="text-muted-foreground text-center text-sm">
                <span>{{ $t('trans.ui_already_have_an_account') }} </span>
                <TextLink :href="route('login')" class="hover:text-primary underline-offset-4 transition-colors hover:underline">
                    {{ $t('trans.ui_log_in') }}
                </TextLink>
            </div>
        </AuthCard>
    </form>
</template>
