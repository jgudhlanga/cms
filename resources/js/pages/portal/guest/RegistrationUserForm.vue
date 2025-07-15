<script setup lang="ts">
import ApplicationCover from '@/components/auth/ApplicationCover.vue';
import { BaseButton } from '@/components/core/button';
import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { useGuestPortal } from '@/composables/students/useGuestPortal';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { useCreateUserFormStore } from '@/store/portal/useCreateUserFormStore';
import { CreateApplicationUserParams } from '@/types/portal';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';
import BaseInput from '../../../components/core/form/text/BaseInput.vue';
import { Separator } from '@/components/ui/separator';

const { createPortalUser } = useGuestPortal();
const { navigateTo } = useUtils();
const { email, first_name, last_name, middle_name, password, password_confirmation } = storeToRefs(useCreateUserFormStore());
const form = useForm<CreateApplicationUserParams>({
    password_confirmation: '',
    email: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    password: '',
});

const updateForm = () => {
    form.password_confirmation = password_confirmation.value;
    form.email = email.value;
    form.first_name = first_name.value;
    form.last_name = last_name.value ?? '';
    form.middle_name = middle_name?.value ?? '';
    form.password = password.value;
};
const submitForm = () => {
    updateForm();
    createPortalUser(form);
};
const { logout } = useAuth();

onMounted(async () => {
    logout();
});
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <ApplicationCover>
        <header>
            <h3 class="text-primary mt-3 flex items-center justify-center text-lg font-bold uppercase">Harare Polytechnic</h3>
            <p class="text-muted-foreground my-1 text-sm">{{ $t('trans.application_form_description') }}</p>
        </header>
        <form @submit.prevent="submitForm()" class="flex w-1/4 flex-col">
            <div class="flex w-full flex-col space-y-3 p-8 shadow-lg rounded-lg">
                <BaseInput
                    input-id="first_name"
                    :label="$t('trans.first_name')"
                    v-model="first_name"
                    placeholder="enter firstname"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'first_name')"
                    :error="form.errors.first_name"
                />
                <BaseInput
                    input-id="middle_name"
                    :label="$t('trans.middle_name')"
                    placeholder="enter middlename"
                    v-model="middle_name"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="last_name"
                    :label="$t('trans.last_name')"
                    placeholder="enter lastname / surname"
                    v-model="last_name"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'last_name')"
                    :error="form.errors.last_name"
                />
                <BaseInput
                    input-id="email"
                    :label="$t('trans.email')"
                    v-model="email"
                    :label-uppercase="true"
                    :is-required="true"
                    placeholder="enter email"
                    @input="clearFormErrors(form, 'email')"
                    :error="form.errors.email"
                />
                <BaseInput
                    input-id="password"
                    :label="$t('trans.password')"
                    :label-uppercase="true"
                    placeholder="enter password"
                    v-model="password"
                    :type="TextFieldType.password"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password')"
                    :error="form.errors.password"
                />
                <BaseInput
                    input-id="password_confirmation"
                    :label="$t('trans.confirm_password')"
                    :label-uppercase="true"
                    placeholder="confirm password"
                    v-model="password_confirmation"
                    :type="TextFieldType.password"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password_confirmation')"
                    :error="form.errors.password_confirmation"
                />
                <Separator class="my-4" />
                <BaseButton :size="ButtonSize.lg" type="submit" class="w-full" :processing="form.processing">
                    {{ $t('trans.submit') }}
                </BaseButton>
                <BaseButton
                    @click="() => navigateTo(route('login'))"
                    class="mt-1 w-full"
                    :variant="ColorVariant.primary_outline"
                    type="button"
                    :tabindex="7"
                >
                    {{ $t('trans.existing_student_login') }}
                </BaseButton>
            </div>
        </form>
    </ApplicationCover>
</template>
