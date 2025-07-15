<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox, Email, Password } from '@/components/core/form';
import TextLink from '@/components/core/util/TextLink.vue';
import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { Login } from '@/types/auth';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { login } = useAuth();
const { navigateTo } = useUtils();
const form = useForm<Login>({
    email: 'penstejdevelopers@gmail.com',
    password: 'Developer123!',
    remember: true,
});
</script>

<template>
    <Head :title="$t('trans.login')" />
    <form @submit.prevent="login(form)" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <Email v-model="form.email" :inputAutoFocus="true" :tabindex="1" :error="form.errors.email" @input="clearFormErrors(form, 'email')" />
            <Password v-model="form.password" :tabindex="2" :error="form.errors.password" @input="clearFormErrors(form, 'password')" />
            <div class="flex items-center justify-between" :tabindex="3">
                <BaseCheckbox input-id="remember" v-model="form.remember" :tabindex="4" :label="$t('trans.remember_me')" />
                <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                    {{ $t('trans.forgot_password') }}
                </TextLink>
            </div>
            <BaseButton class="mt-4" type="submit" :tabindex="6" :processing="form.processing">
                {{ $t('trans.login') }}
            </BaseButton>
            <BaseButton
                @click="() => navigateTo(route('portal.create'))"
                class="mt-2"
                :variant="ColorVariant.primary_outline"
                type="button"
                :tabindex="7"
            >
                {{ $t('trans.new_student_registration') }}
            </BaseButton>
        </div>
    </form>
</template>
