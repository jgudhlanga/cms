<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import AcademicLevelComboSelect from '@/components/core/form/combobox/AcademicLevelComboSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicRecords } from '@/composables/students/useAcademicRecords';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { AcademicRecord, AcademicRecordParams } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const academicRecord = ref<AcademicRecord>();
const form = useForm<AcademicRecordParams>({
    academicLevel: null,
    academic_level_id: null,
    school: '',
    place: '',
    from_level: null,
    to_level: null,
    from_year: '',
    to_year: '',
    student_unique_number: '',
    exam_board: '',
    exam_month: '',
    exam_year: '',
    exam_center: '',
    exam_results: '',
});

const { updateAcademicRecord, createAcademicRecord } = useAcademicRecords();

const { modals } = useModalStore();

watch(modals!, () => {
    academicRecord.value = getModalEdit(APP_MODULE_KEYS.academic_records);
    form.academic_level_id = academicRecord.value?.attributes?.academicLevelId ?? '';
    form.academicLevel = {
        value: Number(academicRecord.value?.attributes?.academicLevelId ?? ''),
        label: academicRecord.value?.attributes?.academicLevel ?? '',
    };
    form.school = academicRecord.value?.attributes?.school ?? '';
    form.place = academicRecord.value?.attributes?.place ?? '';
    form.from_level = academicRecord.value?.attributes?.fromLevel ?? null;
    form.to_level = academicRecord.value?.attributes?.toLevel ?? null;
    form.from_year = academicRecord.value?.attributes?.fromYear ?? '';
    form.to_year = academicRecord.value?.attributes?.toYear ?? '';
    form.student_unique_number = academicRecord.value?.attributes?.studentUniqueNumber ?? '';
    form.exam_board = academicRecord.value?.attributes?.examBoard ?? '';
    form.exam_month = academicRecord.value?.attributes?.examMonth ?? '';
    form.exam_year = academicRecord.value?.attributes?.examYear ?? '';
    form.exam_center = academicRecord.value?.attributes?.examCenter ?? '';
    form.exam_results = academicRecord.value?.attributes?.examResults ?? '';
    form.defaults();
});

const save = () => {
    form.academic_level_id = form.academicLevel?.value ?? null;
    if (Number(academicRecord.value?.id?.toString()) > 0) {
        updateAcademicRecord(form, academicRecord.value);
    } else {
        createAcademicRecord(form);
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.academic_records"
        :title="`${academicRecord ? $t('trans.update') : $t('trans.create')} ${$t('trans.academic_record')}`"
        :on-form-action="() => save()"
        :size="SizeVariant.lg"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <AcademicLevelComboSelect :form="form" v-model="form.academicLevel" :error="form.errors.academicLevel" />
                <BaseInput
                    input-id="school"
                    :label="$tChoice('trans.school', 1)"
                    v-model="form.school"
                    placeholder="enter school / institution"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'school')"
                    :error="form.errors.school"
                />
                <BaseInput
                    input-id="place"
                    :label="$tChoice('trans.place', 1)"
                    v-model="form.place"
                    placeholder="enter place / city / town / village etc..."
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'place')"
                    :error="form.errors.place"
                />
                <BaseInput
                    input-id="from_level"
                    :label="$t('trans.from_level')"
                    v-model="form.from_level"
                    placeholder="enter start level"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'from_level')"
                    :error="form.errors.from_level"
                />
                <BaseInput
                    input-id="to_level"
                    :label="$t('trans.to_level')"
                    v-model="form.to_level"
                    placeholder="enter end level"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'to_level')"
                    :error="form.errors.to_level"
                />
                <BaseInput
                    input-id="from_year"
                    :label="$t('trans.from_year')"
                    v-model="form.from_year"
                    placeholder="enter start date"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'from_year')"
                    :error="form.errors.from_year"
                />
                <BaseInput
                    input-id="to_year"
                    :label="$t('trans.to_year')"
                    v-model="form.to_year"
                    placeholder="enter end date"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'to_year')"
                    :error="form.errors.to_year"
                />
                <BaseInput
                    input-id="student_unique_number"
                    :label="$t('trans.student_unique_number')"
                    v-model="form.student_unique_number"
                    placeholder="enter candidate #"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'student_unique_number')"
                    :error="form.errors.student_unique_number"
                />
                <BaseInput
                    input-id="exam_board"
                    :label="$t('trans.exam_board')"
                    v-model="form.exam_board"
                    placeholder="enter exam board (ZIMSEC, CAMBRIGE)"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'exam_board')"
                    :error="form.errors.exam_board"
                />
                <BaseInput
                    input-id="exam_month"
                    :label="$t('trans.exam_month')"
                    v-model="form.exam_month"
                    placeholder="enter month (June / Nov ete...)"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'exam_month')"
                    :error="form.errors.exam_month"
                />
                <BaseInput
                    input-id="exam_year"
                    :label="$t('trans.exam_year')"
                    v-model="form.exam_year"
                    placeholder="enter year of exam"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'exam_year')"
                    :error="form.errors.exam_year"
                />
                <BaseInput
                    input-id="exam_center"
                    :label="$t('trans.exam_center')"
                    v-model="form.exam_center"
                    placeholder="enter exam center"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'exam_center')"
                    :error="form.errors.exam_center"
                />
                <BaseInput
                    input-id="exam_results"
                    :label="$t('trans.exam_results')"
                    v-model="form.exam_results"
                    placeholder="enter exam results"
                    :label-uppercase="true"
                    @input="clearFormErrors(form, 'exam_results')"
                    :error="form.errors.exam_results"
                />
            </div>
        </template>
    </BaseModal>
</template>
