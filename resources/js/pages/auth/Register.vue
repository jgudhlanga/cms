<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

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
    <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{{ $tChoice('trans.name', 1) }}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    v-model="form.name"
                    :placeholder="$t('trans.ui_full_name')"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ $t('trans.email_address') }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    v-model="form.email"
                    :placeholder="$t('trans.ui_email_example_com')"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ $t('trans.password') }}</Label>
                <Input
                    id="password"
                    type="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    v-model="form.password"
                    :placeholder="$t('trans.password')"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{ $t('trans.ui_confirm_password_2') }}</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    :placeholder="$t('trans.ui_confirm_password_2')"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ $t('trans.ui_create_account') }}
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-sm">
            {{ $t('trans.ui_already_have_an_account') }}
            <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">{{ $t('trans.ui_log_in') }}</TextLink>
        </div>
    </form>
</template>
