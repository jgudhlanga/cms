<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox, EmailInputWithIcon, PasswordInputWithToggle } from '@/components/core/form';
import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { clearFormErrors } from '@/lib/forms';
import { Login } from '@/types/auth';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { login } = useAuth();
const { getQueryParams } = useUtils();
const { navigateToRegistrationOrMaintenance } = useRegistrationAvailability();
const form = useForm<Login>({
    email: '',
    password: '',
    remember: false,
});

onMounted(() => {
    const params = getQueryParams();
    if (params.email) {
        form.email = params.email;
    }
});

const loginNavigateTo = () => {
    navigateToRegistrationOrMaintenance(route('portal.create'));
};
</script>

<template>
    <Head :title="$t('trans.login')" />
    <BaseAlert v-if="status" :type="TypeVariant.success" :description="status" />
    <form @submit.prevent="login(form)" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.sign_in_with_email')" :subtitle="$t('trans.sign_in_subtitle')">
            <div class="space-y-4">
                <EmailInputWithIcon
                    v-model="form.email"
                    :input-auto-focus="true"
                    :error="form.errors.email"
                    :label="$t('trans.email')"
                    :placeholder="$t('trans.auth_email_placeholder')"
                    :is-required="true"
                    @input="clearFormErrors(form, 'email')"
                />
                <PasswordInputWithToggle
                    v-model="form.password"
                    :error="form.errors.password"
                    :label="$t('trans.password')"
                    :placeholder="$t('trans.enter_your_password')"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password')"
                />
            </div>

            <div class="flex items-center justify-between gap-4">
                <BaseCheckbox input-id="remember" v-model="form.remember" :label="$t('trans.remember_me')" />
                <TextLink
                    :href="route('password.request')"
                    class="pointer-anchor inline-flex min-h-11 items-center text-sm underline-offset-4 hover:underline"
                >
                    {{ $t('trans.forgot_password') }}
                </TextLink>
            </div>

            <div class="space-y-3">
                <BaseButton
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.lg"
                    type="submit"
                    :processing="form.processing"
                    classes="min-h-12 w-full rounded-xl normal-case bg-gradient-to-b from-primary to-primary/80 shadow-lg shadow-primary/30 dark:text-white"
                >
                    {{ $t('trans.sign_in') }}
                </BaseButton>
                <BaseButton
                    @click="() => loginNavigateTo()"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.lg"
                    type="button"
                    :disabled="form.processing"
                    classes="min-h-12 w-full rounded-xl normal-case dark:border-white dark:text-white dark:hover:border-white/80 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    {{ $t('trans.new_student_registration') }}
                </BaseButton>
            </div>
        </AuthCard>
    </form>
</template>
