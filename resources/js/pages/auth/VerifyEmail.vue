<script setup lang="ts">
import TextLink from '@/components/core/util/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <Head :title="$t('trans.ui_email_verification')" />
    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400"
    >
        {{ $t('trans.ui_a_new_verification_link_has_been_sent_to_the_email_address_y') }}
    </div>

    <form
        @submit.prevent="submit"
        class="space-y-6 rounded-lg border border-border bg-card p-6 text-center text-card-foreground shadow-md dark:shadow-sm"
    >
        <Button :disabled="form.processing" variant="secondary">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
            {{ $t('trans.ui_resend_verification_email') }}
        </Button>
        <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm"> {{ $t('trans.ui_log_out') }}</TextLink>
    </form>
</template>
