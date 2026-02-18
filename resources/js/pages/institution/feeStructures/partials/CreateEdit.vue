<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import FeeTypeComboSelect from '@/components/core/form/combobox/FeeTypeComboSelect.vue';
import LevelComboSelect from '@/components/core/form/combobox/LevelComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useFeeStructures } from '@/composables/institution/useFeeStructures';
import { getModalEdit, getModalParent } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { FeeStructure, FeeStructureParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const feeStructure = ref<FeeStructure | null>(null);
const feeType = ref<SelectOption | null>(null);
const level = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);



const { saveFeeStructure } = useFeeStructures();

const { modals } = useModalStore();

watch(modals!, () => {
    feeStructure.value = getModalEdit(APP_MODULE_KEYS.fee_structures);
    const feeTypeModel = getModalParent(APP_MODULE_KEYS.fee_structures);
    feeType.value = feeTypeModel ? { value: Number(feeTypeModel.id), label: feeTypeModel.attributes.name } : null;
    level.value = feeStructure.value
        ? { value: Number(feeStructure.value?.attributes?.levelId ?? ''), label: feeStructure.value?.attributes?.level ?? '' }
        : null;
    modeOfStudy.value = feeStructure.value
        ? { value: Number(feeStructure.value?.attributes?.modeOfStudyId ?? ''), label: feeStructure.value?.attributes?.modeOfStudy ?? '' }
        : null;
    form.amount = feeStructure.value ? (feeStructure.value?.attributes?.amount ?? null) : null;
    form.local_fca_amount = feeStructure.value ? (feeStructure.value?.attributes?.localFcaAmount ?? null) : null;
    form.defaults();
});

const save = () => {
    form.fee_type_id = feeType.value ? Number(feeType.value.value) : null;
    form.level_id = level.value ? Number(level.value.value) : null;
    form.mode_of_study_id = modeOfStudy.value ? Number(modeOfStudy.value.value) : null;
    saveFeeStructure(form, feeStructure.value!);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.fee_structures"
        :title="`${feeStructure ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.fee_structure', 1)}`"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <FeeTypeComboSelect
                    :form="form"
                    v-model="feeType"
                    :error="form.errors.fee_type_id"
                    :label-uppercase="true"
                    :is-required="true"
                    disabled
                />
                <LevelComboSelect :form="form" v-model="level" :error="form.errors.level_id" :label-uppercase="true" />
                <ModeOfStudyComboSelect :form="form" v-model="modeOfStudy" :error="form.errors.mode_of_study_id" :label-uppercase="true" />
                <BaseInput
                    input-id="local_fca_amount"
                    :label="$t('trans.amount_in_us')"
                    v-model="form.local_fca_amount"
                    @input="clearFormErrors(form, 'local_fca_amount')"
                    :error="form.errors.local_fca_amount"
                    :label-uppercase="true"
                />
                <BaseInput
                    input-id="amount"
                    :label="$t('trans.local_amount')"
                    v-model="form.amount"
                    @input="clearFormErrors(form, 'amount')"
                    :error="form.errors.amount"
                    :label-uppercase="true"
                />
            </div>
        </template>
    </BaseModal>
</template>
