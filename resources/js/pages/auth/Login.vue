<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox, Email, Password } from '@/components/core/form';
import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { clearFormErrors } from '@/lib/forms';
import ToastService from '@/services/toast.service';
import { Login } from '@/types/auth';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { login } = useAuth();
const { navigateTo, isItTrue, getQueryParams } = useUtils();
const form = useForm<Login>({
    email: '',
    password: '',
    remember_me: false,
});

const maintenanceMode = isItTrue(import.meta.env.VITE_MAINTENANCE_MODE);

onMounted(() => {
    const params = getQueryParams();
    if (params.email) {
        form.email = params.email;
    }
});

const loginNavigateTo = () => {
    if (maintenanceMode) {
        ToastService.warning('Sorry, system updates underway, we will be back soon');
        return;
    }
    navigateTo(route('portal.create'));
};
</script>

<template>
    <Head :title="$t('trans.login')" />
    <BaseAlert v-if="status" :type="TypeVariant.success" :description="status" />
    <form @submit.prevent="login(form)" class="flex w-full flex-col">
        <div class="flex flex-col space-y-4 rounded-lg p-5 shadow-md">
            <Email
                v-model="form.email"
                :inputAutoFocus="true"
                :tabindex="1"
                :error="form.errors.email"
                @input="clearFormErrors(form, 'email')"
                :label-uppercase="true"
                :is-required="true"
            />
            <Password
                v-model="form.password"
                :tabindex="2"
                :error="form.errors.password"
                @input="clearFormErrors(form, 'password')"
                :label-uppercase="true"
                :is-required="true"
            />
            <div class="flex items-center justify-between" :tabindex="3">
                <BaseCheckbox input-id="remember" v-model="form.remember_me" :tabindex="4" :label="$t('trans.remember_me')" />
                <BaseButton
                    type="button"
                    :variant="ColorVariant.danger_outline"
                    @click="() => navigateTo(route('password.request'))"
                    :size="ButtonSize.xs"
                    classes="rounded-full"
                >
                    {{ $t('trans.forgot_password') }}
                </BaseButton>
            </div>
            <BaseButton class="mt-4" type="submit" :tabindex="6" :processing="form.processing">
                {{ $t('trans.login') }}
            </BaseButton>
            <BaseButton
                @click="() => loginNavigateTo()"
                class="mt-1"
                :variant="ColorVariant.primary_outline"
                type="button"
                :tabindex="7"
                :disabled="form.processing"
            >
                {{ $t('trans.new_student_registration') }}
            </BaseButton>
        </div>
    </form>
</template>
