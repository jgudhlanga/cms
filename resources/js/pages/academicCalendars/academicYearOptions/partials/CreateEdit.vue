<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicYearOptions } from '@/composables/academicCalendars/useAcademicYearOptions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicYearOption, AcademicYearOptionParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const academicYearOption = ref<AcademicYearOption>();
const form = useForm<AcademicYearOptionParams>({
    name: '',
    description: '',
});

const { save } = useAcademicYearOptions();

const { modals } = useModalStore();

watch(modals!, () => {
    academicYearOption.value = getModalEdit(APP_MODULE_KEYS.academic_year_options);
    form.name = academicYearOption.value?.attributes?.name ?? '';
    form.description = academicYearOption.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.academic_year_options"
        :title="`${academicYearOption ? $t('trans.edit') : $t('trans.create')} ${$tChoice('academic_years.calendar_year_option', 1)}`"
        :on-form-action="() => save(form, academicYearOption)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
