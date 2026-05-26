<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { TextFieldType } from '@/enums/inputs';

defineProps<{
    firstName: string;
    middleName: string;
    lastName: string;
    email: string;
    password: string;
    passwordConfirmation: string;
    processing: boolean;
    errors: Record<string, string | undefined>;
    passwordMatches: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:firstName', value: string): void;
    (e: 'update:middleName', value: string): void;
    (e: 'update:lastName', value: string): void;
    (e: 'update:email', value: string): void;
    (e: 'update:password', value: string): void;
    (e: 'update:passwordConfirmation', value: string): void;
    (e: 'clear-error', field: string): void;
    (e: 'back'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <form class="flex flex-col space-y-3" @submit.prevent="emit('submit')">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase text-foreground">
                {{ $t('trans.enrollment_step_account') }}
            </h2>
            <button type="button" class="text-primary text-xs font-medium underline" @click="emit('back')">
                {{ $t('trans.back') }}
            </button>
        </div>

        <BaseInput
            input-id="first_name"
            :model-value="firstName"
            :placeholder="$t('trans.ui_enter_first_name')"
            :vertical-layout="false"
            :label-uppercase="true"
            :is-required="true"
            :error="errors.first_name"
            @update:model-value="emit('update:firstName', $event)"
            @input="emit('clear-error', 'first_name')"
        />
        <BaseInput
            input-id="middle_name"
            :model-value="middleName"
            :label="$t('trans.middle_name')"
            :placeholder="$t('trans.ui_enter_middlename')"
            :vertical-layout="false"
            :label-uppercase="true"
            :error="errors.middle_name"
            @update:model-value="emit('update:middleName', $event)"
            @input="emit('clear-error', 'middle_name')"
        />
        <BaseInput
            input-id="last_name"
            :model-value="lastName"
            :placeholder="$t('trans.ui_enter_surname')"
            :label-uppercase="true"
            :is-required="true"
            :vertical-layout="false"
            :error="errors.last_name"
            @update:model-value="emit('update:lastName', $event)"
            @input="emit('clear-error', 'last_name')"
        />
        <BaseInput
            input-id="email"
            :model-value="email"
            :label-uppercase="true"
            :is-required="true"
            :vertical-layout="false"
            :placeholder="$t('trans.ui_enter_email')"
            :error="errors.email"
            @update:model-value="emit('update:email', $event)"
            @input="emit('clear-error', 'email')"
        />
        <BaseInput
            input-id="password"
            :label-uppercase="true"
            :placeholder="$t('trans.ui_enter_password')"
            :model-value="password"
            :type="TextFieldType.password"
            :vertical-layout="false"
            :is-required="true"
            :error="errors.password"
            @update:model-value="emit('update:password', $event)"
            @input="emit('clear-error', 'password')"
        />
        <BaseInput
            input-id="password_confirmation"
            :label-uppercase="true"
            :placeholder="$t('trans.ui_confirm_password')"
            :model-value="passwordConfirmation"
            :type="TextFieldType.password"
            :is-required="true"
            :vertical-layout="false"
            :error="errors.password_confirmation"
            @update:model-value="emit('update:passwordConfirmation', $event)"
            @input="emit('clear-error', 'password_confirmation')"
        />
        <p v-if="!passwordMatches" class="text-sm text-red-600 lowercase dark:text-red-400">
            {{ $t('trans.ui_password_and_confirm_password_do_not_match') }}
        </p>

        <BaseButton type="submit" class="mt-3 w-full" :processing="processing">
            {{ $t('trans.submit') }}
        </BaseButton>
    </form>
</template>
