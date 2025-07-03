<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useApplicationSteps } from '@/composables/shared/useApplicationSteps';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { ApplicationStep, ApplicationStepParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const applicationStep = ref<ApplicationStep>();
const form = useForm<ApplicationStepParams>({
    name: '',
    description: '',
});

const { saveApplicationStep } = useApplicationSteps();

const { modals } = useModalStore();

watch(modals!, () => {
    applicationStep.value = getModalEdit(APP_MODULE_KEYS.application_steps);
    form.name = applicationStep.value?.attributes?.name ?? '';
    form.description = applicationStep.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.application_steps"
        :title="`${applicationStep ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.application_step', 1)}`"
        :on-form-action="() => saveApplicationStep(form, applicationStep)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
