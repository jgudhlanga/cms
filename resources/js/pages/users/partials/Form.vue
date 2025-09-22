<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import RoleSelect from '@/components/core/form/select/RoleSelect.vue';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import PhoneNumber from '@/components/core/form/text/PhoneNumber.vue';
import { useUsers } from '@/composables/users/useUsers';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { User, UserParams } from '@/types/users';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    edit: boolean;
    user?: User;
}

const props = withDefaults(defineProps<Props>(), {
    edit: false,
});
const { user } = props;

const form = useForm<UserParams>({
    email: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    role_ids: [],
});

const { saveUser, validateFormSchema } = useUsers();
const {navigateTo} = useUtils()

const isValidating = ref(false);
const save = async () => {
    try {
        isValidating.value = true;
        await validateFormSchema(user?.id.toString()).parseAsync(form);
        saveUser(form, user?.id.toString() ?? '');
    } catch (error: any) {
        form.setError(error.format());
    } finally {
        isValidating.value = false;
    }
};

onMounted(() => {
    if (props.edit && user) {
        form.first_name = user.attributes.firstname ?? '';
        form.last_name = user.attributes.lastname ?? '';
        form.middle_name = user.attributes.middleName ?? '';
        form.phone_number = user.attributes.phoneNumber ?? '';
        form.email = user.attributes.email ?? '';
        form.role_ids = user.relationships.roles?.map((role) => role.id);
    }
});

const onlyRoles = 'head-of-department,head-of-division,lecturer,lecturer-in-charge,senior-lecturer';
</script>

<template>
    <form @submit.prevent="() => save()">
        <BaseCard :title="$t('trans.personal_details')" :description="$t('trans.personal_details_description')">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <BaseInput
                    input-id="first_name"
                    :label="$t('trans.first_name')"
                    v-model="form.first_name"
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
                    v-model="form.middle_name"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="last_name"
                    :label="$t('trans.last_name')"
                    placeholder="enter lastname / surname"
                    v-model="form.last_name"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'last_name')"
                    :error="form.errors.last_name"
                />
                <PhoneNumber
                    v-model="form.phone_number"
                    placeholder="enter phone number"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'phone_number')"
                    :error="form.errors.phone_number"
                />
            </div>
        </BaseCard>
        <div class="mt-6 flex flex-col justify-center" v-if="!edit">
            <BaseCard :title="$t('trans.login_details')" :description="$t('trans.login_details_description')">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <EmailAddress
                        v-model="form.email"
                        :label-uppercase="true"
                        :is-required="true"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                    <BaseInput
                        input-id="password"
                        :label="$t('trans.password')"
                        :label-uppercase="true"
                        placeholder="enter password"
                        v-model="form.password"
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
                        v-model="form.password_confirmation"
                        :type="TextFieldType.password"
                        :is-required="true"
                        @input="clearFormErrors(form, 'password_confirmation')"
                        :error="form.errors.password_confirmation"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="my-8 flex flex-col">
            <BaseCard :title="$tChoice('trans.role', 2)" :description="$t('trans.role_details_description')">
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3">
                    <RoleSelect
                        :url="`api/v1/acl/roles?page_size=all`"
                        :label-uppercase="true"
                        :is-multi="true"
                        :is-searchable="true"
                        v-model="form.role_ids"
                    />
                </div>
            </BaseCard>
        </div>
        <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
            <BaseButton type="button" :variant="ColorVariant.shade" @click="() => navigateTo(route('users.index'))" :size="ButtonSize.lg">
                {{ $t('trans.back') }}
            </BaseButton>
            <BaseButton :processing="form.processing || isValidating" :size="ButtonSize.lg">
                {{ $t('trans.save') }}
            </BaseButton>
        </div>
    </form>
</template>
