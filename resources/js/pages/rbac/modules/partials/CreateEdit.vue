<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useModules } from '@/composables/rbac/useModules';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Module, ModuleParams } from '@/types/rbac';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const module = ref<Module>();
const { saveModule } = useModules();
const form = useForm<ModuleParams>({
    title: '',
    description: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    module.value = getModalEdit(APP_MODULE_KEYS.modules);
    form.title = module.value?.attributes?.title ?? '';
    form.description = module.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.modules"
        :title="`${module ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.module', 1)}`"
        :on-form-action="() => saveModule(form, module)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
            <Description v-model="form.description" />
        </template>
    </BaseModal>
</template>
