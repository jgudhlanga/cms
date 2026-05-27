<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm<any>({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.ui_reset_password')" />
    <form
        @submit.prevent="submit"
        class="rounded-lg border border-border bg-card p-6 text-card-foreground shadow-md dark:shadow-sm"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ $t('trans.email') }}</Label>
                <Input id="email" type="email" name="email" autocomplete="email" v-model="form.email" class="mt-1 block w-full" readonly />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ $t('trans.password') }}</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    autofocus
                    :placeholder="$t('trans.password')"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation"> {{ $t('trans.confirm_password') }}</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    class="mt-1 block w-full"
                    :placeholder="$t('trans.ui_confirm_password_2')"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-4 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ $t('trans.ui_reset_password') }}
            </Button>
        </div>
    </form>
</template>
