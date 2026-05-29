<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import { useUsers } from '@/composables/users/useUsers';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { AuthCredentialsUpdate, User } from '@/types/users';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    user?: User;
    hideAuthorization?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hideAuthorization: false,
});
const { user } = props;

const form = useForm<AuthCredentialsUpdate>({
    email: '',
    password: '',
    password_confirmation: '',
    change_email: false,
    change_password: false,
});

const { updateUserCredentials, isValidating, loadUserPermissions, userPermissions, isLoading } = useUsers();
const initialEmail = ref('');
const changeEmail = ref(false);
const changePassword = ref(false);
const emailDirty = computed(() => form.email.trim() !== initialEmail.value.trim());
const passwordDirty = computed(() => form.password.trim().length > 0 || form.password_confirmation.trim().length > 0);
const canSubmit = computed(
    () => (changeEmail.value && emailDirty.value) || (changePassword.value && passwordDirty.value),
);

watch(changeEmail, (enabled) => {
    if (!enabled) {
        form.email = initialEmail.value;
        clearFormErrors(form, 'email');
    }
});

watch(changePassword, (enabled) => {
    if (!enabled) {
        form.password = '';
        form.password_confirmation = '';
        clearFormErrors(form, 'password');
        clearFormErrors(form, 'password_confirmation');
    }
});

const submitForm = () => {
    form.change_email = changeEmail.value;
    form.change_password = changePassword.value;

    if (!changeEmail.value && emailDirty.value) {
        form.setError('email', 'Tick Change Email to save an email update.');
        return;
    }

    if (!changePassword.value && passwordDirty.value) {
        form.setError('password', 'Tick Change Password to save a password update.');
        return;
    }

    if (changeEmail.value && !emailDirty.value) {
        form.setError('email', 'Email has not changed.');
        return;
    }

    if (changePassword.value && !passwordDirty.value) {
        form.setError('password', 'Enter password details to change the password.');
        return;
    }

    if (changePassword.value && form.password_confirmation !== form.password) {
        form.setError('password_confirmation', 'Passwords do not match.');
        return;
    }

    updateUserCredentials(form, String(user?.id), {
        validateEmail: changeEmail.value,
        validatePassword: changePassword.value,
    });
};

onMounted(async () => {
    if (user) {
        form.email = user.attributes.email ?? '';
        initialEmail.value = user.attributes.email ?? '';
        if (!props.hideAuthorization) {
            await loadUserPermissions(route('v1.users.permissions', { id: user.id }));
        }
    }
});

</script>

<template>
    <form @submit.prevent="() => submitForm()">
        <div class="flex flex-col justify-center space-y-6">
            <BaseCard
                :title="$t('trans.ui_login_profile')"
                :description="$t('trans.change_login_credentials_warning')"
                color-variant="black"
            >
                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <BaseCheckbox input-id="change_email" v-model="changeEmail" label="Change Email" />
                    <BaseCheckbox input-id="change_password" v-model="changePassword" label="Change Password" />
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <EmailAddress
                        v-model="form.email"
                        :label-uppercase="true"
                        :is-required="changeEmail"
                        :disabled="!changeEmail"
                        @input="clearFormErrors(form, 'email')"
                        :error="form.errors.email"
                    />
                    <BaseInput
                        input-id="password"
                        :label="$t('trans.password')"
                        :label-uppercase="true"
                        :placeholder="$t('trans.ui_enter_password')"
                        v-model="form.password"
                        :type="TextFieldType.password"
                        :vertical-layout="true"
                        :is-required="changePassword"
                        :disabled="!changePassword"
                        @input="clearFormErrors(form, 'password')"
                        :error="form.errors.password"
                    />
                    <BaseInput
                        input-id="password_confirmation"
                        :label="$t('trans.confirm_password')"
                        :label-uppercase="true"
                        :placeholder="$t('trans.ui_confirm_password')"
                        v-model="form.password_confirmation"
                        :type="TextFieldType.password"
                        :is-required="changePassword"
                        :disabled="!changePassword"
                        :vertical-layout="true"
                        @input="clearFormErrors(form, 'password_confirmation')"
                        :error="form.errors.password_confirmation"
                    />
                </div>
                <div class="flex w-full px-6 pt-5">
                    <BaseButton :processing="form.processing || isValidating" :disabled="!canSubmit" :variant="ColorVariant.warning">
                        {{ $t('trans.save') }}
                    </BaseButton>
                </div>
            </BaseCard>
            <BaseCard
                v-if="!hideAuthorization"
                :title="$t('trans.authorization')"
                :description="$t('trans.roles_and_permissions')"
                color-variant="black"
            >
                <DataLoadingSpinner v-if="isLoading"/>
                    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-x-3" >
                        <div v-for="(permission, index) in userPermissions" :key="index">{{ permission?.attributes?.name }}</div>
                    </div>
            </BaseCard>
        </div>
    </form>
</template>
