<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import IdTypeComboSelect from '@/components/core/form/combobox/IdTypeComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import PassportNumber from '@/components/core/form/text/PassportNumber.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useUsers } from '@/composables/users/useUsers';
import { ColorVariant } from '@/enums/colors';
import { clearFormErrors } from '@/lib/forms';
import { StudentUserEditParams, User } from '@/types/users';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    user?: User;
}

const props = defineProps<Props>();
const { user } = props;

const form = useForm<StudentUserEditParams>({
    id_type_id: '',
    idType: null,
    id_number: '',
    passport_number: '',
    country: null,
    country_id: '',
    date_of_birth: '',
    maritalStatus: null,
    marital_status_id: '',
    title: null,
    title_id: '',
    gender: null,
    gender_id: '',
    email: '',
    phone_number: '',
    first_name: '',
    last_name: '',
    middle_name: '',
});

const { updateStudentUserSchema, updateStudentUser } = useUsers();
const { navigateTo, isNativeCitizen } = useUtils();

const isValidating = ref(false);

const updateForm = (isNative: boolean) => {
    Object.assign(form, {
        gender_id: form.gender?.value ?? '',
        title_id: form.title?.value ?? '',
        country_id: isNative ? null : (form.country?.value ?? null),
        id_type_id: form.idType?.value ?? '',
        marital_status_id: form.maritalStatus?.value ?? null,
    });
};
const save = async () => {
    const isNative = isNativeCitizen(form.idType?.label ?? '');
    updateForm(isNative);
    const userId = String(user?.id);
    const studentId = user?.attributes?.studentId ? String(user?.attributes?.studentId) : '';
    try {
        isValidating.value = true;
        await updateStudentUserSchema(isNative, userId, studentId).parseAsync(form);
        updateStudentUser(form, userId);
    } catch (error: any) {
        form.setError(error.format());
    } finally {
        isValidating.value = false;
    }
};

onMounted(() => {
    if (user) {
        form.first_name = user.attributes.firstname ?? '';
        form.last_name = user.attributes.lastname ?? '';
        form.middle_name = user.attributes.middleName ?? '';
        form.phone_number = user.attributes.phoneNumber ?? '';
        form.email = user.attributes.email ?? '';
        form.gender = { value: Number(user?.relationships?.profile?.genderId ?? ''), label: user?.relationships?.profile?.gender ?? '' };
        form.title = { value: Number(user?.relationships?.profile?.titleId ?? ''), label: user?.relationships?.profile?.title ?? '' };
        form.country = { value: Number(user?.relationships?.profile?.countryId ?? ''), label: user?.relationships?.profile?.country ?? '' };
        form.maritalStatus = {
            value: Number(user?.relationships?.profile?.maritalStatusId ?? ''),
            label: user?.relationships?.profile?.maritalStatus ?? '',
        };
        form.idType = {
            value: Number(user?.relationships?.profile?.idTypeId ?? ''),
            label: user?.relationships?.profile?.idType ?? '',
        };
        form.date_of_birth = user?.relationships?.profile?.dateOfBirth ?? '';
        form.id_number = user?.relationships?.profile?.idNumber ?? '';
        form.passport_number = user?.relationships?.profile?.passportNumber ?? '';
    }
});
</script>

<template>
    <form @submit.prevent="() => save()">
        <BaseCard :title="$t('trans.personal_details')" :description="$t('trans.personal_details_description')">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <BaseInput
                    input-id="first_name"
                    :label="$t('trans.first_name')"
                    v-model="form.first_name"
                    :placeholder="$t('trans.ui_enter_firstname')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'first_name')"
                    :error="form.errors.first_name"
                />
                <BaseInput
                    input-id="middle_name"
                    :label="$t('trans.middle_name')"
                    :placeholder="$t('trans.ui_enter_middlename')"
                    v-model="form.middle_name"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="last_name"
                    :label="$t('trans.last_name')"
                    :placeholder="$t('trans.ui_enter_lastname_surname')"
                    v-model="form.last_name"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'last_name')"
                    :error="form.errors.last_name"
                />
                <PhoneNumber
                    v-model="form.phone_number"
                    :placeholder="$t('trans.ui_enter_phone_number')"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'phone_number')"
                    :error="form.errors.phone_number"
                />
            </div>
        </BaseCard>
        <div class="mt-6 flex flex-col justify-center">
            <BaseCard :title="$t('trans.ui_student_profile')" :description="$t('trans.ui_student_profile_details')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <TitleComboSelect :form="form" v-model="form.title" :error="form.errors.title" :is-required="true" :label-uppercase="true" />
                    <GenderComboSelect :form="form" v-model="form.gender" :error="form.errors.gender" :is-required="true" :label-uppercase="true" />
                    <MaritalStatusComboSelect
                        :form="form"
                        v-model="form.maritalStatus"
                        :error="form.errors.maritalStatus"
                        :is-required="true"
                        :label-uppercase="true"
                    />
                    <IdTypeComboSelect :form="form" v-model="form.idType" :error="form.errors.idType" :label-uppercase="true" :is-required="true" />
                    <template v-if="isNativeCitizen(form.idType?.label ?? '')">
                        <IdNumber
                            v-model="form.id_number"
                            :is-required="true"
                            @input="clearFormErrors(form, 'id_number')"
                            :error="form.errors.id_number"
                        />
                    </template>
                    <template v-else>
                        <PassportNumber
                            v-model="form.passport_number"
                            :is-required="true"
                            @input="clearFormErrors(form, 'passport_number')"
                            :error="form.errors.passport_number"
                        />
                        <CountryComboSelect
                            :form="form"
                            v-model="form.country"
                            :error="form.errors.country"
                            :label-uppercase="true"
                            :is-required="true"
                        />
                    </template>
                    <DateOfBirth
                        v-model="form.date_of_birth"
                        :is-required="true"
                        :label-uppercase="true"
                        :teleport="true"
                        :error="form.errors.date_of_birth"
                        @update:model-value="clearFormErrors(form, 'date_of_birth')"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="mt-6 flex flex-col justify-center">
            <BaseCard
                :title="$t('trans.ui_login_profile')"
                :description="$t('trans.ui_user_will_create_change_password_through_the_forgot_password')"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <EmailAddress
                        v-model="form.email"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="mt-6 flex w-full justify-center space-x-3 px-6 py-5">
            <BaseButton type="button" :variant="ColorVariant.shade" @click="() => navigateTo(route('users.index'))">
                {{ $t('trans.back') }}
            </BaseButton>
            <BaseButton :processing="form.processing || isValidating">
                {{ $t('trans.save') }}
            </BaseButton>
        </div>
    </form>
</template>
