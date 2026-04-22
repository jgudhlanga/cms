<script setup lang="ts">
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAssessmentTypes } from '@/composables/institution/useAssessmentTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AssessmentType, AssessmentTypeParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    modesOfStudy: Array<{ id: number; name: string }>;
}>();

const assessmentType = ref<AssessmentType>();
const selectedModes = ref<Array<SelectOption | string | number>>([]);
const form = useForm<AssessmentTypeParams>({
    name: '',
    modes_of_study: [],
    description: '',
});

const modeOfStudyOptions = computed<Array<SelectOption>>(() =>
    props.modesOfStudy.map((mode) => ({
        value: mode.id,
        label: mode.name,
    })),
);

const getOptionValue = (option: SelectOption | string | number): number =>
    Number(typeof option === 'object' && option !== null ? option.value : option);

const { saveAssessmentType } = useAssessmentTypes();
const { modals } = useModalStore();

watch(modals!, () => {
    assessmentType.value = getModalEdit(APP_MODULE_KEYS.assessment_types);
    form.name = assessmentType.value?.attributes?.name ?? '';
    form.description = assessmentType.value?.attributes?.description ?? '';
    form.modes_of_study = (assessmentType.value?.attributes?.modesOfStudyIds ?? [])
        .map((option) => getOptionValue(option))
        .filter((value) => Number.isInteger(value) && value > 0);

    selectedModes.value = [...form.modes_of_study];
    form.defaults();
});

watch(selectedModes, () => {
    form.modes_of_study = selectedModes.value
        .map((option) => getOptionValue(option))
        .filter((value) => Number.isInteger(value) && value > 0);
    clearFormErrors(form, 'modes_of_study');
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.assessment_types"
        :title="`${assessmentType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.assessment_type', 1)}`"
        :on-form-action="() => saveAssessmentType(form, assessmentType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <BaseSelect
                :label="$tChoice('trans.mode_of_study', 2)"
                v-model="selectedModes"
                :options="modeOfStudyOptions"
                :is-multi="true"
                :error="form.errors.modes_of_study"
            />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
