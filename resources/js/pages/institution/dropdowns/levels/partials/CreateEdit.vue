<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useLevels } from '@/composables/institution/useLevels';
import { TextFieldType } from '@/enums/inputs';
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
    allowed_applications_per_level: '',
});

const { saveLevel } = useLevels();

const { modals } = useModalStore();

watch(modals!, () => {
    level.value = getModalEdit(APP_MODULE_KEYS.levels);
    form.name = level.value?.attributes?.name ?? '';
    form.description = level.value?.attributes?.description ?? '';
    form.allowed_applications_per_level = Number(level.value?.attributes?.allowedApplicationsPerLevel) ?? '';
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
            <BaseInput
                :label="$t('trans.allowed_applications_per_level')"
                v-model="form.allowed_applications_per_level"
                :type="TextFieldType.number"
                input-id="allowed_applications_per_level"
            />
        </template>
    </BaseModal>
</template>
