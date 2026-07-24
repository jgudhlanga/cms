<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useRoles } from '@/composables/rbac/useRoles';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Role, RoleParams } from '@/types/rbac';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const role = ref<Role>();
const { saveRole } = useRoles();
const form = useForm<RoleParams>({
    name: '',
    description: '',
    permissions: [],
});

const { modals } = useModalStore();

watch(modals!, () => {
    role.value = getModalEdit(APP_MODULE_KEYS.roles);
    form.name = role.value?.attributes?.name ?? '';
    form.description = role.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.roles"
        :title="`${role ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.role', 1)}`"
        :on-form-action="() => saveRole(form, role)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" />
        </template>
    </BaseModal>
</template>
