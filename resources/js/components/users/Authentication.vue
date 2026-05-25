<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import { useUsers } from '@/composables/users/useUsers';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { AuthCredentialsUpdate, User } from '@/types/users';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    user?: User;
}

const props = defineProps<Props>();
const { user } = props;

const form = useForm<AuthCredentialsUpdate>({
    email: '',
    password: '',
    password_confirmation: '',
});

const { updateUserCredentials, isValidating, loadUserPermissions, userPermissions, isLoading } = useUsers();
const passwordMatches = ref(true);

const submitForm = () => {
    if (form.password_confirmation !== form.password) {
        passwordMatches.value = false;
        return;
    } else { 
        passwordMatches.value = true;
    }
    updateUserCredentials(form, String(user?.id));
};

onMounted(async () => {
    if (user) {
        form.email = user.attributes.email ?? '';
        await loadUserPermissions(route('v1.users.permissions', { id: user.id }));
    }
});

</script>

<template>
    <form @submit.prevent="() => submitForm()">
        <div class="flex flex-col justify-center space-y-6">
            <BaseCard
                :title="$t('trans.ui_login_profile')"
                :description="$t('trans.change_login_credentials_warning')"
                color-variant="amber-500"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                        :placeholder="$t('trans.ui_enter_password')"
                        v-model="form.password"
                        :type="TextFieldType.password"
                        :vertical-layout="true"
                        :is-required="true"
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
                        :is-required="true"
                        :vertical-layout="true"
                        @input="clearFormErrors(form, 'password_confirmation')"
                        :error="form.errors.password_confirmation"
                    />
                </div>
                <div class="flex w-full px-6 pt-5">
                    <BaseButton :processing="form.processing || isValidating" :variant="ColorVariant.warning">
                        {{ $t('trans.save') }}
                    </BaseButton>
                </div>
            </BaseCard>
            <BaseCard
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
