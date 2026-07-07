<script setup lang="ts">
import AuthCard from '@/components/auth/AuthCard.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <Head :title="$t('trans.ui_email_verification')" />
    <BaseAlert
        v-if="props.status === 'verification-link-sent'"
        :type="TypeVariant.success"
        :description="$t('trans.ui_a_new_verification_link_has_been_sent_to_the_email_address_y')"
    />
    <form @submit.prevent="submit" class="flex w-full flex-col">
        <AuthCard :title="$t('trans.ui_email_verification')" :subtitle="$t('trans.verify_email_subtitle')">
            <BaseButton
                :variant="ColorVariant.primary"
                type="submit"
                :tabindex="1"
                :processing="form.processing"
                classes="min-h-11 w-full rounded-xl dark:text-white"
            >
                {{ $t('trans.ui_resend_verification_email') }}
            </BaseButton>

            <div class="text-center">
                <TextLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
                    :tabindex="2"
                >
                    {{ $t('trans.ui_log_out') }}
                </TextLink>
            </div>
        </AuthCard>
    </form>
</template>
