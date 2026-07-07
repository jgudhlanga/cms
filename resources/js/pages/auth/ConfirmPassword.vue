<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import { BaseButton } from '@/components/core/button';
import { PasswordInputWithToggle } from '@/components/core/form';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm<any>({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.ui_confirm_password_2')" />
    <form @submit.prevent="submit" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.confirm_password')" :subtitle="$t('trans.confirm_password_description')">
            <div class="space-y-4">
                <PasswordInputWithToggle
                    v-model="form.password"
                    :input-auto-focus="true"
                    :tabindex="1"
                    :error="form.errors.password"
                    :label="$t('trans.password')"
                    :placeholder="$t('trans.password')"
                    :is-required="true"
                    autocomplete="current-password"
                    @input="clearFormErrors(form, 'password')"
                />
            </div>

            <BaseButton
                :variant="ColorVariant.primary"
                type="submit"
                :tabindex="2"
                :processing="form.processing"
                classes="min-h-11 w-full rounded-xl dark:text-white"
            >
                {{ $t('trans.confirm_password') }}
            </BaseButton>
        </AuthCard>
    </form>
</template>
