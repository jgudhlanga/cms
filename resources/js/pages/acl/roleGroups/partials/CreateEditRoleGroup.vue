<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useRoleGroups } from '@/composables/acl/useRoleGroups';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { RoleGroup, RoleGroupParams } from '@/types/acl';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const roleGroup = ref<RoleGroup>();
const { saveRoleGroup } = useRoleGroups();
const form = useForm<RoleGroupParams>({
    name: '',
    description: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    roleGroup.value = getModalEdit(APP_MODULE_KEYS.role_groups);
    form.name = roleGroup.value?.attributes?.name ?? '';
    form.description = roleGroup.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.role_groups"
        :title="`${roleGroup ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.role_group', 1)}`"
        :on-form-action="() => saveRoleGroup(form, roleGroup)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" />
        </template>
    </BaseModal>
</template>
