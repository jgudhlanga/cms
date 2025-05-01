<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useLevels } from '@/composables/institution/useLevels';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Level, LevelParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const level = ref<Level>();
const form = useForm<LevelParams>({
    name: '',
    description: '',
});

const { saveLevel } = useLevels();

const { modals } = useModalStore();

watch(modals!, () => {
    level.value = getModalEdit(APP_MODULE_KEYS.levels);
    form.name = level.value?.attributes?.name ?? '';
    form.description = level.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.levels"
        :title="`${level ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.level', 1)}`"
        :on-form-action="() => saveLevel(form, level)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
