<script setup lang="ts">
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import AppLogo from '@/components/core/image/AppLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';
import BaseInput from '../../components/core/form/text/BaseInput.vue';

import { BaseCheckbox } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import DistrictComboSelect from '@/components/core/form/combobox/DistrictComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import ProvinceComboSelect from '@/components/core/form/combobox/ProvinceComboSelect.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { TextFieldType } from '@/enums/inputs';
import { useApplicationFormStore } from '@/store/applications/useApplicationFormStore';
import { CreateApplicationParams } from '@/types/applications';
import { storeToRefs } from 'pinia';

const {
    email,
    first_name,
    last_name,
    middle_name,
    password,
    title,
    id_number,
    passport_number,
    country,
    gender,
    address1,
    address2,
    address3,
    address4,
    province,
    district,
    confirm_password,
} = storeToRefs(useApplicationFormStore());
const form = useForm<CreateApplicationParams>({
    address1: '',
    address2: '',
    address3: '',
    address4: '',
    confirm_password: '',
    country: null,
    country_id: null,
    district: null,
    district_id: null,
    gender: null,
    gender_id: null,
    id_number: '',
    passport_number: '',
    province: null,
    province_id: null,
    email: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    password: '',
    title: null,
    title_id: null,
});
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <div class="flex flex-col items-center justify-center space-y-4 overflow-scroll py-8">
        <div class="my-5 flex size-25 flex-col items-center justify-center">
            <AppLogo classes="border-2 border-white" />
        </div>
        <header>
            <h3 class="text-primary text-md my-3 flex items-center justify-center font-bold uppercase">Harare Polytechnic</h3>
            <p class="text-muted-foreground my-2 text-sm">{{ $t('trans.application_form_description') }}</p>
        </header>
        <div class="flex w-2/5 flex-col space-y-3 rounded-2xl p-8 shadow-md">
            <HeadingSmall :title="$t('trans.personal_details')" :description="$t('trans.personal_details_description')" />
            <TitleComboSelect :form="form" v-model="title" :error="form.errors.title" :vertical-layout="false" />
            <BaseInput input-id="first_name" :label="$t('trans.first_name')" :vertical-layout="false" v-model="first_name" placeholder="" />
            <BaseInput input-id="middle_name" :label="$t('trans.middle_name')" :vertical-layout="false" placeholder="" v-model="middle_name" />
            <BaseInput input-id="last_name" :label="$t('trans.last_name')" :vertical-layout="false" placeholder="" v-model="last_name" />
            <GenderComboSelect :form="form" v-model="gender" :error="form.errors.gender" :vertical-layout="false" />
            <BaseCheckbox input-id="is_zimbabwean_citizen" :label="$t('trans.is_zimbabwean_citizen')" />
            <BaseInput input-id="id_number" :label="$t('trans.id_number')" :vertical-layout="false" placeholder="" v-model="id_number" />
            <BaseInput
                input-id="passport_number"
                :label="$t('trans.passport_number')"
                :vertical-layout="false"
                placeholder=""
                v-model="passport_number"
            />
            <CountryComboSelect :form="form" v-model="country" :error="form.errors.country" :vertical-layout="false" />
        </div>
        <div class="flex w-2/5 flex-col space-y-3 rounded-2xl p-8 shadow-md">
            <HeadingSmall :title="$t('trans.login_details')" :description="$t('trans.login_details_description')" />
            <BaseInput input-id="email" :label="$t('trans.email')" :vertical-layout="false" placeholder="" v-model="email" />
            <BaseInput
                input-id="password"
                :label="$t('trans.password')"
                :vertical-layout="false"
                placeholder=""
                v-model="password"
                :type="TextFieldType.password"
            />
            <BaseInput
                input-id="confirm_password"
                :label="$t('trans.confirm_password')"
                :vertical-layout="false"
                placeholder=""
                v-model="confirm_password"
                :type="TextFieldType.password"
            />
        </div>
        <div class="flex w-2/5 flex-col space-y-3 rounded-2xl p-8 shadow-md">
            <HeadingSmall :title="$t('trans.address_details')" :description="$t('trans.address_details_description')" />
            <BaseInput input-id="address1" :label="$tChoice('trans.house_number', 1)" :vertical-layout="false" placeholder="" v-model="address1" />
            <BaseInput input-id="address2" :label="$tChoice('trans.street', 1)" :vertical-layout="false" placeholder="" v-model="address2" />
            <BaseInput input-id="address3" :label="$t('trans.suburb_village')" :vertical-layout="false" placeholder="" v-model="address3" />
            <BaseInput input-id="address4" :label="$t('trans.city_town_suburb')" :vertical-layout="false" placeholder="" v-model="address4" />
            <ProvinceComboSelect :form="form" v-model="province" :error="form.errors.province" :vertical-layout="false" />
            <DistrictComboSelect :form="form" v-model="district" :error="form.errors.district" :vertical-layout="false" />
        </div>
    </div>
</template>
