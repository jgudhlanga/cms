<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import CountryComboSelect from '@/components/core/form/combobox/CountryComboSelect.vue';
import GenderComboSelect from '@/components/core/form/combobox/GenderComboSelect.vue';
import IdTypeComboSelect from '@/components/core/form/combobox/IdTypeComboSelect.vue';
import MaritalStatusComboSelect from '@/components/core/form/combobox/MaritalStatusComboSelect.vue';
import RaceComboSelect from '@/components/core/form/combobox/RaceComboSelect.vue';
import ReligionComboSelect from '@/components/core/form/combobox/ReligionComboSelect.vue';
import TitleComboSelect from '@/components/core/form/combobox/TitleComboSelect.vue';
import DateOfBirth from '@/components/core/form/date/DateOfBirth.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import PassportNumber from '@/components/core/form/text/PassportNumber.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Student, StudentPersonalDetailParams } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const { isNativeCitizen } = useUtils();
const student = ref<Student | null>(null);

const { updateStudent, updateStudentSchema } = useStudentPortal();

const form = useForm<StudentPersonalDetailParams>({
    country: null,
    country_id: null,
    date_of_birth: '',
    denomination: '',
    gender: null,
    gender_id: null,
    height: '',
    idType: null,
    id_number: '',
    id_type_id: null,
    maritalStatus: null,
    marital_status_id: null,
    passport_number: '',
    race: null,
    race_id: null,
    religion: null,
    religion_id: null,
    title: null,
    title_id: null,
    weight: '',
});

const { modals } = useModalStore();

watch(modals!, () => {
    student.value = getModalEdit(APP_MODULE_KEYS.student_personal_details);
    form.title = { value: Number(student.value?.titleId ?? ''), label: student.value?.title ?? '' };
    form.gender = { value: Number(student.value?.genderId ?? ''), label: student.value?.gender ?? '' };
    form.maritalStatus = {
        value: Number(student.value?.maritalStatusId ?? ''),
        label: student.value?.maritalStatus ?? '',
    };
    form.idType = { value: Number(student.value?.idTypeId ?? ''), label: student.value?.idType ?? '' };
    form.country = { value: Number(student.value?.countryId ?? '') ?? null, label: student.value?.country ?? '' };
    form.race = { value: Number(student.value?.raceId ?? ''), label: student.value?.race ?? '' };
    form.religion = { value: Number(student.value?.religionId ?? ''), label: student.value?.religion ?? '' };
    form.id_number = student.value?.idNumber ?? '';
    form.passport_number = student.value?.passportNumber ?? '';
    form.date_of_birth = student.value?.dateOfBirth ?? '';
    form.denomination = student.value?.denomination ?? '';
    form.height = student.value?.height ?? '';
    form.weight = student.value?.weight ?? '';
});

const updateForm = () => {
    const isNative = isNativeCitizen(form.idType?.label ?? '');
    Object.assign(form, {
        gender_id: form.gender?.value ?? '',
        title_id: form.title?.value ?? '',
        country_id: isNative ? null : (form.country?.value ?? null),
        id_type_id: form.idType?.value ?? '',
        marital_status_id: form.maritalStatus?.value ?? null,
        race_id: form.race?.value ?? null,
        religion_id: form.religion?.value ?? null,
    });
};
const save = async () => {
    updateForm();
    const studentId = student.value?.id?.toString() ?? '';
    const isNative = isNativeCitizen(form.idType?.label ?? '');
    try {
        await updateStudentSchema(isNative, studentId).parseAsync(form);
        await updateStudent(studentId, form);
    } catch (error: any) {
        form.setError(error.format());
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.student_personal_details"
        :title="`${student ? $t('trans.update') : $t('trans.create')} ${$tChoice('trans.student', 1)}`"
        :on-form-action="() => save()"
        :size="SizeVariant.lg"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <TitleComboSelect :form="form" v-model="form.title" :error="form.errors.title" :is-required="true" :label-uppercase="true" />
                <GenderComboSelect :form="form" v-model="form.gender" :error="form.errors.gender" :is-required="true" :label-uppercase="true" />
                <MaritalStatusComboSelect
                    :form="form"
                    v-model="form.maritalStatus"
                    :error="form.errors.maritalStatus"
                    :is-required="true"
                    :label-uppercase="true"
                />
                <IdTypeComboSelect :form="form" v-model="form.idType" :error="form.errors.idType" :label-uppercase="true" :is-required="true" />
                <template v-if="isNativeCitizen(form.idType?.label ?? '')">
                    <IdNumber
                        v-model="form.id_number"
                        :is-required="true"
                        @input="clearFormErrors(form, 'id_number')"
                        :error="form.errors.id_number"
                    />
                </template>
                <template v-else>
                    <PassportNumber
                        v-model="form.passport_number"
                        :is-required="true"
                        @input="clearFormErrors(form, 'passport_number')"
                        :error="form.errors.passport_number"
                    />
                    <CountryComboSelect
                        :form="form"
                        v-model="form.country"
                        :error="form.errors.country"
                        :label-uppercase="true"
                        :is-required="true"
                    />
                </template>
                <DateOfBirth
                    v-model="form.date_of_birth"
                    :is-required="true"
                    :label-uppercase="true"
                    :teleport="true"
                    :error="form.errors.date_of_birth"
                    @update:model-value="clearFormErrors(form, 'date_of_birth')"
                />
                <RaceComboSelect :form="form" v-model="form.race" :error="form.errors.race" :label-uppercase="true" />
                <ReligionComboSelect :form="form" v-model="form.religion" :error="form.errors.religion" :label-uppercase="true" />
                <BaseInput
                    input-id="denomination"
                    :label="$tChoice('trans.denomination', 1)"
                    v-model="form.denomination"
                    placeholder="enter denomination"
                    :label-uppercase="true"
                    :error="form.errors.denomination"
                />
                <BaseInput
                    input-id="height"
                    :label="$t('trans.height')"
                    v-model="form.height"
                    placeholder="enter height"
                    :label-uppercase="true"
                    :error="form.errors.height"
                />
                <BaseInput
                    input-id="weight"
                    :label="$t('trans.weight')"
                    v-model="form.weight"
                    placeholder="enter weight"
                    :label-uppercase="true"
                    :error="form.errors.weight"
                />
            </div>
        </template>
    </BaseModal>
</template>
