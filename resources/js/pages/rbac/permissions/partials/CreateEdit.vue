<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { usePermissions } from '@/composables/rbac/usePermissions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Permission, PermissionParams } from '@/types/rbac';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const permission = ref<Permission>();
const { savePermission } = usePermissions();
const form = useForm<PermissionParams>({
    name: '',
    description: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    permission.value = getModalEdit(APP_MODULE_KEYS.permissions);
    form.name = permission.value?.attributes?.name ?? '';
    form.description = permission.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.permissions"
        :title="`${permission ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.permission', 1)}`"
        :on-form-action="() => savePermission(form, permission)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" />
        </template>
    </BaseModal>
</template>
