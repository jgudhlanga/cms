<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import AppLogo from '@/components/core/image/AppLogo.vue';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
//import { useAuth } from '@/composables/auth/useAuth';
import { useUtils } from '@/composables/core/useUtils';
import { useGuestPortal } from '@/composables/students/useGuestPortal';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { useCreateUserFormStore } from '@/store/portal/useCreateUserFormStore';
import { CreateApplicationUserParams } from '@/types/portal';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';
import BaseInput from '../../../components/core/form/text/BaseInput.vue';
//import ToastService from '@/services/toast.service';

const { createPortalUser } = useGuestPortal();
const { navigateTo } = useUtils();
const { email, first_name, last_name, password, password_confirmation } = storeToRefs(useCreateUserFormStore());
const form = useForm<CreateApplicationUserParams>({
    password_confirmation: '',
    email: '',
    first_name: '',
    last_name: '',
    password: '',
});

const passwordMatches = ref(true);

const updateForm = () => {
    form.password_confirmation = password_confirmation.value;
    form.email = email.value;
    form.first_name = first_name.value;
    form.last_name = last_name.value ?? '';
    form.password = password.value;
};
const submitForm = () => {
    updateForm();
    if (password_confirmation.value !== password.value) {
        passwordMatches.value = false;
        return;
    } else {
        passwordMatches.value = true;
    }
    createPortalUser(form);
};
// const { logout } = useAuth();

onMounted(async () => {
    //logout();
    //ToastService.warning('Sorry, The registration has ended for now. Contact the administration for more info.');
    //navigateTo(route('login'));
});
const { isItTrue } = useUtils();
const maintenanceMode = isItTrue(import.meta.env.VITE_MAINTENANCE_MODE);
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <div class="flex justify-between bg-white">
        <ComingSoonAnimated v-if="maintenanceMode" :title="$t('trans.ui_sorry')" message='Enrolment has been closed'/>
        <div v-else class="flex w-full flex-col p-3 md:w-1/2 md:p-16">
            <form @submit.prevent="submitForm()" class="flex flex-col rounded-2xl p-10 shadow-md">
                <div class="flex w-full items-center justify-center">
                    <div class="size-18">
                        <AppLogo classes="flex justify-center border-2 border-white" />
                    </div>
                </div>
                <div class="text-primary mt-13 mb-7 flex items-center justify-center text-lg font-bold uppercase">{{ $t('trans.ui_harare_polytechnic') }}</div>
                <div class="mb-4 px-1 uppercase">
                    {{ $t('trans.ui_attention') }} <code class="font-bold text-red-600">{{ $t('trans.ui_ecocash') }}</code> users: To avoid network failures, please use separate devices when
                    making your payments. Thank you for your cooperation.
                </div>
                <div class="flex w-full flex-col space-y-3">
                    <BaseInput
                        input-id="first_name"
                        label=""
                        v-model="first_name"
                        :placeholder="$t('trans.ui_enter_first_name')"
                        :vertical-layout="false"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'first_name')"
                        :error="form.errors.first_name"
                    />
                    <BaseInput
                        input-id="last_name"
                        label=""
                        :placeholder="$t('trans.ui_enter_surname')"
                        v-model="last_name"
                        :label-uppercase="true"
                        :is-required="true"
                        :vertical-layout="false"
                        @input="clearFormErrors(form, 'last_name')"
                        :error="form.errors.last_name"
                    />
                    <BaseInput
                        input-id="email"
                        label=""
                        v-model="email"
                        :label-uppercase="true"
                        :is-required="true"
                        :vertical-layout="false"
                        :placeholder="$t('trans.ui_enter_email')"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                    <BaseInput
                        input-id="password"
                        label=""
                        :label-uppercase="true"
                        :placeholder="$t('trans.ui_enter_password')"
                        v-model="password"
                        :type="TextFieldType.password"
                        :vertical-layout="false"
                        :is-required="true"
                        @input="clearFormErrors(form, 'password')"
                        :error="form.errors.password"
                    />
                    <BaseInput
                        input-id="password_confirmation"
                        label=""
                        :label-uppercase="true"
                        :placeholder="$t('trans.ui_confirm_password')"
                        v-model="password_confirmation"
                        :type="TextFieldType.password"
                        :is-required="true"
                        :vertical-layout="false"
                        @input="clearFormErrors(form, 'password_confirmation')"
                        :error="form.errors.password_confirmation"
                    />
                    <div class="flex justify-end text-sm font-extralight text-red-600 lowercase dark:text-red-500" v-if="!passwordMatches">
                        {{ $t('trans.ui_password_and_confirm_password_do_not_match') }}
                    </div>
                    <div class="mt-5 flex flex-col items-center justify-center space-y-3">
                        <BaseButton type="submit" class="w-full md:w-1/2" :processing="form.processing">
                            {{ $t('trans.submit') }}
                        </BaseButton>
                        <BaseButton
                            @click="() => navigateTo(route('login'))"
                            class="w-full md:w-1/2"
                            :variant="ColorVariant.primary_outline"
                            type="button"
                            :tabindex="7"
                            :disabled="form.processing"
                        >
                            {{ $t('trans.returning_student_login') }}
                        </BaseButton>
                    </div>
                </div>
            </form>
        </div>
        <RegistrationGuide />
    </div>
</template>
