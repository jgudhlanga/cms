<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useDivisions } from '@/composables/institution/useDivisions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Division, DivisionParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const division = ref<Division>();
const form = useForm<DivisionParams>({
    name: '',
    description: '',
});

const { saveDivision } = useDivisions();

const { modals } = useModalStore();

watch(modals!, () => {
    division.value = getModalEdit(APP_MODULE_KEYS.divisions);
    form.name = division.value?.attributes?.name ?? '';
    form.description = division.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.divisions"
        :title="`${division ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.division', 1)}`"
        :on-form-action="() => saveDivision(form, division)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
