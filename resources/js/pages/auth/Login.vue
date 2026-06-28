<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox, EmailInputWithIcon, PasswordInputWithToggle } from '@/components/core/form';
import AppLogo from '@/components/core/image/AppLogo.vue';
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
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
        <div
            class="relative flex w-full flex-col gap-6 rounded-3xl border border-white/40 bg-white/60 p-8 text-card-foreground shadow-xl backdrop-blur-xl dark:border-border/50 dark:bg-card/70"
        >
            <div class="absolute top-4 right-4">
                <AppearanceCycleToggle />
            </div>

            <div class="flex flex-col items-center gap-3 text-center">
                <AppLogo classes="size-14 object-contain" />
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold tracking-tight text-foreground">
                        {{ $t('trans.sign_in_with_email') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ $t('trans.sign_in_subtitle') }}
                    </p>
                </div>
            </div>

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
                <PasswordInputWithToggle
                    v-model="form.password"
                    :tabindex="2"
                    :error="form.errors.password"
                    :label="$t('trans.password')"
                    :placeholder="$t('trans.password')"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password')"
                />
            </div>

            <div class="flex items-center justify-between gap-4">
                <BaseCheckbox input-id="remember" v-model="form.remember" :tabindex="3" :label="$t('trans.remember_me')" />
                <button
                    type="button"
                    class="text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
                    :tabindex="4"
                    @click="() => navigateTo(route('password.request'))"
                >
                    {{ $t('trans.forgot_password') }}
                </button>
            </div>

            <div class="space-y-3">
                <BaseButton
                    :variant="ColorVariant.primary"
                    type="submit"
                    :tabindex="5"
                    :processing="form.processing"
                    classes="min-h-11 w-full rounded-xl dark:text-white"
                >
                    {{ $t('trans.login') }}
                </BaseButton>
                <BaseButton
                    @click="() => loginNavigateTo()"
                    :variant="ColorVariant.primary_outline"
                    type="button"
                    :tabindex="6"
                    :disabled="form.processing"
                    classes="min-h-11 w-full rounded-xl dark:border-white dark:text-white dark:hover:border-white/80 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    {{ $t('trans.new_student_registration') }}
                </BaseButton>
            </div>
        </div>
    </form>
</template>
