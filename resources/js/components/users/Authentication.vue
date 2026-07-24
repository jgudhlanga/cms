<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput, BasePasswordInput } from '@/components/core/form';
import EmailAddress from '@/components/core/form/text/EmailAddress.vue';
import ProfileFieldCard from '@/components/users/profile/ProfileFieldCard.vue';
import { useUsers } from '@/composables/users/useUsers';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { clearFormErrors } from '@/lib/forms';
import { scrollToFirstError } from '@/lib/scrollToFirstError';
import { AuthCredentialsUpdate, AuthNamesUpdate, User } from '@/types/users';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, nextTick, onMounted, ref } from 'vue';
import { PageProps } from '@/types';

interface Props {
    user?: User;
    hideAuthorization?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hideAuthorization: false,
});
const { user } = props;
const { isItTrue } = useUtils();
const page = usePage<PageProps>();
const isImpersonating = computed(() => isItTrue(page.props.auth.impersonating));

const MASKED_PASSWORD = '••••••••';

const namesForm = useForm<AuthNamesUpdate>({
    first_name: '',
    middle_name: '',
    last_name: '',
});

const form = useForm<AuthCredentialsUpdate>({
    email: '',
    password: '',
    password_confirmation: '',
    change_email: false,
    change_password: false,
});

const { updateUserCredentials, updateUserNames, isValidating, loadUserPermissions, userPermissions, isLoading } = useUsers();
const initialEmail = ref('');
const initialFirstName = ref('');
const initialMiddleName = ref('');
const initialLastName = ref('');
const changeName = ref(false);
const changeEmail = ref(false);
const changePassword = ref(false);
const nameToggleRef = ref<HTMLButtonElement | null>(null);
const emailToggleRef = ref<HTMLButtonElement | null>(null);
const passwordToggleRef = ref<HTMLButtonElement | null>(null);

const fullNameDisplay = computed(() => {
    const parts = [initialFirstName.value, initialMiddleName.value, initialLastName.value]
        .map((part) => part.trim())
        .filter(Boolean);

    return parts.join(' ');
});

const namesDirty = computed(
    () =>
        namesForm.first_name.trim() !== initialFirstName.value.trim()
        || namesForm.middle_name.trim() !== initialMiddleName.value.trim()
        || namesForm.last_name.trim() !== initialLastName.value.trim(),
);
const emailDirty = computed(() => form.email.trim() !== initialEmail.value.trim());
const passwordDirty = computed(() => form.password.trim().length > 0 || form.password_confirmation.trim().length > 0);
const passwordMatches = computed(() => !form.password || form.password === form.password_confirmation);
const canSubmitNames = computed(() => changeName.value && namesDirty.value);
const canSubmitEmail = computed(() => changeEmail.value && emailDirty.value);
const canSubmitPassword = computed(
    () => changePassword.value && passwordDirty.value && passwordMatches.value && form.password.trim().length > 0,
);

const resetNameFields = () => {
    namesForm.first_name = initialFirstName.value;
    namesForm.middle_name = initialMiddleName.value;
    namesForm.last_name = initialLastName.value;
    clearFormErrors(namesForm, 'first_name');
    clearFormErrors(namesForm, 'middle_name');
    clearFormErrors(namesForm, 'last_name');
};

const resetEmailFields = () => {
    form.email = initialEmail.value;
    clearFormErrors(form, 'email');
};

const resetPasswordFields = () => {
    form.password = '';
    form.password_confirmation = '';
    clearFormErrors(form, 'password');
    clearFormErrors(form, 'password_confirmation');
};

const cancelName = async (returnFocus = true) => {
    changeName.value = false;
    resetNameFields();
    if (returnFocus) {
        await nextTick();
        nameToggleRef.value?.focus();
    }
};

const cancelEmail = async (returnFocus = true) => {
    changeEmail.value = false;
    resetEmailFields();
    if (returnFocus) {
        await nextTick();
        emailToggleRef.value?.focus();
    }
};

const cancelPassword = async (returnFocus = true) => {
    changePassword.value = false;
    resetPasswordFields();
    if (returnFocus) {
        await nextTick();
        passwordToggleRef.value?.focus();
    }
};

const closeOtherPanels = async (except: 'name' | 'email' | 'password') => {
    if (except !== 'name' && changeName.value) {
        await cancelName(false);
    }
    if (except !== 'email' && changeEmail.value) {
        await cancelEmail(false);
    }
    if (except !== 'password' && changePassword.value) {
        await cancelPassword(false);
    }
};

const openNamePanel = async () => {
    await closeOtherPanels('name');
    changeName.value = true;
};

const openEmailPanel = async () => {
    await closeOtherPanels('email');
    changeEmail.value = true;
};

const openPasswordPanel = async () => {
    await closeOtherPanels('password');
    changePassword.value = true;
};

const submitNames = () => {
    if (!namesDirty.value) {
        namesForm.setError('first_name', trans('trans.login_profile_names_not_changed'));
        scrollToFirstError(namesForm.errors);
        return;
    }

    updateUserNames(namesForm, String(user?.id), {
        onSuccess: () => {
            initialFirstName.value = namesForm.first_name;
            initialMiddleName.value = namesForm.middle_name;
            initialLastName.value = namesForm.last_name;
            changeName.value = false;
        },
    });
};

const submitEmail = () => {
    form.change_email = true;
    form.change_password = false;

    if (!emailDirty.value) {
        form.setError('email', trans('trans.login_profile_email_not_changed'));
        scrollToFirstError(form.errors);
        return;
    }

    updateUserCredentials(form, String(user?.id), {
        validateEmail: true,
        validatePassword: false,
        onSuccess: () => {
            initialEmail.value = form.email;
            changeEmail.value = false;
            resetPasswordFields();
        },
    });
};

const submitPassword = () => {
    form.change_email = false;
    form.change_password = true;

    if (!passwordDirty.value) {
        form.setError('password', trans('trans.login_profile_enter_password_details'));
        scrollToFirstError(form.errors);
        return;
    }

    if (!passwordMatches.value) {
        form.setError('password_confirmation', trans('trans.ui_password_and_confirm_password_do_not_match'));
        scrollToFirstError(form.errors);
        return;
    }

    updateUserCredentials(form, String(user?.id), {
        validateEmail: false,
        validatePassword: true,
        onSuccess: () => {
            changePassword.value = false;
            resetPasswordFields();
        },
    });
};

onMounted(async () => {
    if (user) {
        namesForm.first_name = user.attributes.firstname ?? '';
        namesForm.middle_name = user.attributes.middleName ?? '';
        namesForm.last_name = user.attributes.lastname ?? '';
        initialFirstName.value = namesForm.first_name;
        initialMiddleName.value = namesForm.middle_name;
        initialLastName.value = namesForm.last_name;

        form.email = user.attributes.email ?? '';
        initialEmail.value = user.attributes.email ?? '';
        if (!props.hideAuthorization) {
            await loadUserPermissions(route('v1.users.permissions', { id: user.id }));
        }
    }
});
</script>

<template>
    <div class="flex flex-col justify-center space-y-3 py-2">
        <BaseCard :title="$t('trans.ui_full_name')" color-variant="black">
            <BaseAlert
                v-if="isImpersonating"
                class="mb-3"
                :type="TypeVariant.warning"
                :title="$t('trans.ui_remove_impersonation')"
                :description="$t('trans.login_profile_impersonation_names_locked')"
            />

            <div class="space-y-3">
                <ProfileFieldCard
                    :label="$t('trans.ui_full_name')"
                    :value="fullNameDisplay"
                    :is-empty="!fullNameDisplay"
                    :empty-label="$t('trans.not_provided')"
                    class="rounded-lg! px-3! py-2!"
                />

                <button
                    v-if="!changeName && !isImpersonating"
                    ref="nameToggleRef"
                    type="button"
                    class="text-primary text-sm font-medium underline underline-offset-4 hover:text-primary/80"
                    :aria-expanded="false"
                    @click="openNamePanel"
                >
                    {{ $t('trans.change_name') }}
                </button>

                <fieldset
                    v-else-if="changeName"
                    class="space-y-3 rounded-lg border border-border/60 bg-muted/20 p-3"
                >
                    <legend class="sr-only">{{ $t('trans.login_profile_change_name_sr') }}</legend>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <BaseInput
                            input-id="auth_first_name"
                            :label="$t('trans.first_name')"
                            v-model="namesForm.first_name"
                            :input-auto-focus="true"
                            :is-required="true"
                            @input="clearFormErrors(namesForm, 'first_name')"
                            :error="namesForm.errors.first_name"
                        />
                        <BaseInput
                            input-id="auth_middle_name"
                            :label="$t('trans.middle_name')"
                            v-model="namesForm.middle_name"
                            @input="clearFormErrors(namesForm, 'middle_name')"
                            :error="namesForm.errors.middle_name"
                        />
                        <BaseInput
                            input-id="auth_last_name"
                            :label="$t('trans.last_name')"
                            v-model="namesForm.last_name"
                            :is-required="true"
                            @input="clearFormErrors(namesForm, 'last_name')"
                            :error="namesForm.errors.last_name"
                        />
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <BaseButton
                            type="button"
                            class="w-full sm:w-auto"
                            :variant="ColorVariant.shade_outline"
                            @click="cancelName()"
                        >
                            {{ $t('trans.cancel') }}
                        </BaseButton>
                        <BaseButton
                            type="button"
                            class="w-full sm:w-auto"
                            :processing="namesForm.processing || isValidating"
                            :disabled="!canSubmitNames"
                            :variant="ColorVariant.primary"
                            @click="submitNames"
                        >
                            {{ $t('trans.save_changes') }}
                        </BaseButton>
                    </div>
                </fieldset>
            </div>
        </BaseCard>

        <BaseCard :title="$t('trans.ui_login_profile')" color-variant="black">
            <BaseAlert
                v-if="isImpersonating"
                class="mb-3"
                :type="TypeVariant.warning"
                :title="$t('trans.ui_remove_impersonation')"
                :description="$t('trans.login_profile_impersonation_credentials_locked')"
            />

            <p
                v-else
                class="mb-3 text-xs leading-snug text-muted-foreground"
            >
                {{ $t('trans.login_profile_verification_description_short') }}
            </p>

            <div class="space-y-4">
                <section class="space-y-2">
                    <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                        {{ $t('trans.email_address') }}
                    </h2>

                    <ProfileFieldCard
                        :label="$t('trans.email_address')"
                        :value="initialEmail"
                        :is-empty="!initialEmail"
                        :empty-label="$t('trans.not_provided')"
                        class="rounded-lg! px-3! py-2!"
                    />

                    <button
                        v-if="!changeEmail && !isImpersonating"
                        ref="emailToggleRef"
                        type="button"
                        class="text-primary text-sm font-medium underline underline-offset-4 hover:text-primary/80"
                        :aria-expanded="false"
                        @click="openEmailPanel"
                    >
                        {{ $t('trans.change_email') }}
                    </button>

                    <fieldset
                        v-else
                        class="space-y-3 rounded-lg border border-border/60 bg-muted/20 p-3"
                    >
                        <legend class="sr-only">{{ $t('trans.login_profile_change_email_sr') }}</legend>

                        <EmailAddress
                            v-model="form.email"
                            :input-auto-focus="true"
                            :is-required="true"
                            @input="clearFormErrors(form, 'email')"
                            :error="form.errors.email"
                        />

                        <p class="text-xs text-muted-foreground">
                            {{ $t('trans.login_profile_email_helper') }}
                        </p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <BaseButton
                                type="button"
                                class="w-full sm:w-auto"
                                :variant="ColorVariant.shade_outline"
                                @click="cancelEmail()"
                            >
                                {{ $t('trans.cancel') }}
                            </BaseButton>
                            <BaseButton
                                type="button"
                                class="w-full sm:w-auto"
                                :processing="form.processing || isValidating"
                                :disabled="!canSubmitEmail"
                                :variant="ColorVariant.primary"
                                @click="submitEmail"
                            >
                                {{ $t('trans.save_changes') }}
                            </BaseButton>
                        </div>
                    </fieldset>
                </section>

                <section class="space-y-2">
                    <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                        {{ $t('trans.password') }}
                    </h2>

                    <ProfileFieldCard
                        :label="$t('trans.password')"
                        :value="MASKED_PASSWORD"
                        class="rounded-lg! px-3! py-2!"
                    />

                    <button
                        v-if="!changePassword && !isImpersonating"
                        ref="passwordToggleRef"
                        type="button"
                        class="text-primary text-sm font-medium underline underline-offset-4 hover:text-primary/80"
                        :aria-expanded="false"
                        @click="openPasswordPanel"
                    >
                        {{ $t('trans.change_password') }}
                    </button>

                    <fieldset
                        v-else
                        class="space-y-3 rounded-lg border border-border/60 bg-muted/20 p-3"
                    >
                        <legend class="sr-only">{{ $t('trans.login_profile_change_password_sr') }}</legend>

                        <BasePasswordInput
                            input-id="password"
                            :label="$t('trans.password')"
                            :placeholder="$t('trans.ui_enter_password')"
                            v-model="form.password"
                            :input-auto-focus="true"
                            :is-required="true"
                            show-strength
                            autocomplete="new-password"
                            @input="clearFormErrors(form, 'password')"
                            :error="form.errors.password"
                        />

                        <BasePasswordInput
                            input-id="password_confirmation"
                            :label="$t('trans.confirm_password')"
                            :placeholder="$t('trans.ui_confirm_password')"
                            v-model="form.password_confirmation"
                            :is-required="true"
                            autocomplete="new-password"
                            @input="clearFormErrors(form, 'password_confirmation')"
                            :error="form.errors.password_confirmation"
                        />

                        <p
                            v-if="passwordDirty && !passwordMatches"
                            class="text-sm text-red-600 lowercase dark:text-red-400"
                        >
                            {{ $t('trans.ui_password_and_confirm_password_do_not_match') }}
                        </p>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <BaseButton
                                type="button"
                                class="w-full sm:w-auto"
                                :variant="ColorVariant.shade_outline"
                                @click="cancelPassword()"
                            >
                                {{ $t('trans.cancel') }}
                            </BaseButton>
                            <BaseButton
                                type="button"
                                class="w-full sm:w-auto"
                                :processing="form.processing || isValidating"
                                :disabled="!canSubmitPassword"
                                :variant="ColorVariant.primary"
                                @click="submitPassword"
                            >
                                {{ $t('trans.save_changes') }}
                            </BaseButton>
                        </div>
                    </fieldset>
                </section>
            </div>
        </BaseCard>

        <BaseCard
            v-if="!hideAuthorization"
            :title="$t('trans.authorization')"
            :description="$t('trans.roles_and_permissions')"
            color-variant="black"
        >
            <DataLoadingSpinner v-if="isLoading" />
            <div v-else class="grid grid-cols-1 gap-x-3 gap-y-1 text-xs md:grid-cols-4">
                <div v-for="(permission, index) in userPermissions" :key="index">
                    {{ permission?.attributes?.name }}
                </div>
            </div>
        </BaseCard>
    </div>
</template>
