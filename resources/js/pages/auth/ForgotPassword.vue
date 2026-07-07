<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { EmailInputWithIcon } from '@/components/core/form';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { clearFormErrors } from '@/lib/forms';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm<any>({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="$t('trans.ui_forgot_password')" />
    <BaseAlert v-if="status" :type="TypeVariant.success" :description="status" />
    <form @submit.prevent="submit" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.ui_forgot_password')" :subtitle="$t('trans.forgot_password_subtitle')">
            <div class="space-y-4">
                <EmailInputWithIcon
                    v-model="form.email"
                    :input-auto-focus="true"
                    :tabindex="1"
                    :error="form.errors.email"
                    :label="$t('trans.email')"
                    :placeholder="$t('trans.email')"
                    :is-required="true"
                    @input="clearFormErrors(form, 'email')"
                />
            </div>

            <BaseButton
                :variant="ColorVariant.primary"
                type="submit"
                :tabindex="2"
                :processing="form.processing"
                classes="min-h-11 w-full rounded-xl dark:text-white"
            >
                {{ $t('trans.ui_email_password_reset_link') }}
            </BaseButton>

            <div class="text-center text-sm text-muted-foreground space-x-2">
                <span>{{ $t('trans.ui_or_return_to') }} </span>
                <TextLink
                    :href="route('login')"
                    class="underline-offset-4 transition-colors hover:text-primary hover:underline"
                    :tabindex="3"
                >
                    {{ $t('trans.login') }}
                </TextLink>
            </div>
        </AuthCard>
    </form>
</template>
