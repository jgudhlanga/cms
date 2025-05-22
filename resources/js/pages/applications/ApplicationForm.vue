<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import { useApplications } from '@/composables/applications/useApplications';
import { ButtonSize } from '@/enums/buttons';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { useApplicationFormStore } from '@/store/applications/useApplicationFormStore';
import { CreateApplicationParams } from '@/types/applications';
import { Head, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import BaseInput from '../../components/core/form/text/BaseInput.vue';
import ApplicationCover from '@/pages/applications/ApplicationCover.vue';
import TextLink from '@/components/core/util/TextLink.vue';

const { createApplication } = useApplications();
const { email, first_name, last_name, middle_name, password, title, gender, password_confirmation } = storeToRefs(useApplicationFormStore());
const form = useForm<CreateApplicationParams>({
    password_confirmation: '',
    email: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    password: '',
    title: null,
    title_id: null,
    gender: null,
    gender_id: null,
});

const updateForm = () => {
        form.password_confirmation = password_confirmation.value;
        form.email = email.value;
        form.first_name = first_name.value;
        form.last_name = last_name.value ?? '';
        form.middle_name = middle_name?.value ?? '';
        form.password = password.value;
        form.title = title.value ?? null;
        form.title_id = title.value?.value ?? null;
        form.gender = gender.value ?? null;
        form.gender_id = gender.value?.value ?? null;
};
const submitForm = () => {
    updateForm();
    createApplication(form);
};



</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <ApplicationCover>
        <header>
            <h3 class="text-primary my-3 flex items-center justify-center text-lg font-bold uppercase">Harare Polytechnic</h3>
            <p class="text-muted-foreground my-2 text-sm">{{ $t('trans.application_form_description') }}</p>
        </header>
        <form @submit.prevent="submitForm()" class="flex  w-2/5 flex-col">
            <div class="flex w-full flex-col space-y-3 py-8">
                <TitleComboSelect
                    :form="form"
                    v-model="title"
                    :error="form.errors.title"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    :is-required="true"
                />
                <BaseInput
                    input-id="first_name"
                    :label="$t('trans.first_name')"
                    :vertical-layout="false"
                    v-model="first_name"
                    placeholder=""
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'first_name')"
                    :error="form.errors.first_name"
                />
                <BaseInput
                    input-id="middle_name"
                    :label="$t('trans.middle_name')"
                    :vertical-layout="false"
                    placeholder=""
                    v-model="middle_name"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="last_name"
                    :label="$t('trans.last_name')"
                    :vertical-layout="false"
                    placeholder="enter last name / surname"
                    v-model="last_name"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'last_name')"
                    :error="form.errors.last_name"
                />
                <GenderComboSelect
                    :form="form"
                    v-model="gender"
                    :error="form.errors.gender"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    :is-required="true"
                />
                <BaseInput
                    input-id="email"
                    :label="$t('trans.email')"
                    :vertical-layout="false"
                    placeholder=""
                    v-model="email"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'email')"
                    :error="form.errors.email"
                />
                <BaseInput
                    input-id="password"
                    :label="$t('trans.password')"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    placeholder=""
                    v-model="password"
                    :type="TextFieldType.password"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password')"
                    :error="form.errors.password"
                />
                <BaseInput
                    input-id="password_confirmation"
                    :label="$t('trans.confirm_password')"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    placeholder="confirm password"
                    v-model="password_confirmation"
                    :type="TextFieldType.password"
                    :is-required="true"
                    @input="clearFormErrors(form, 'password_confirmation')"
                    :error="form.errors.password_confirmation"
                />
            </div>
            <div class="flex flex-col w-full items-center justify-center space-y-4">
                <BaseButton :size="ButtonSize.lg" type="submit">{{ $t('trans.submit') }}</BaseButton>
                <div class="text-muted-foreground text-center text-sm">
                    {{ $t('trans.have_an_account') }}
                    <TextLink :href="route('login')" :tabindex="7">{{ $t('trans.login') }}</TextLink>
                </div>
            </div>
        </form>
    </ApplicationCover>
</template>
