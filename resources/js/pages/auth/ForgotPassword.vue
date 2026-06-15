<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import InputError from '@/components/core/form/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm<any>({
    email: '',
});

const { navigateTo } = useUtils();

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="$t('trans.ui_forgot_password')" />
    <div
        class="space-y-6 rounded-lg border border-border bg-card p-6 text-card-foreground shadow-md dark:shadow-sm"
    >
        <BaseAlert v-if="status" :type="TypeVariant.success" :description="status" />
        <form @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">{{ $t('trans.email_address') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-model="form.email"
                    autofocus
                    :placeholder="$t('trans.ui_email_example_com')"
                />
                <InputError :message="form.errors.email" />
            </div>
            <div class="my-6 flex items-center justify-start">
                <Button class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ $t('trans.ui_email_password_reset_link') }}
                </Button>
            </div>
        </form>
        <div class="text-muted-foreground space-x-1 text-center text-sm">
            <span>{{ $t('trans.ui_or_return_to') }}</span>
            <BaseButton
                type="button"
                :variant="ColorVariant.danger_outline"
                @click="() => navigateTo(route('login'))"
                :size="ButtonSize.xs"
                classes="rounded-full"
            >
                {{ $t('trans.login') }}
            </BaseButton>
            <!--            <TextLink :href="route('login')">{{ $t('trans.ui_log_in_2') }}</TextLink>-->
        </div>
    </div>
</template>
