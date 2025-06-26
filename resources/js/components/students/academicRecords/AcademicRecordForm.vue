<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
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
    school: '',
    place: '',
    from_level: '',
    to_level: '',
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
    form.school = academicRecord.value?.attributes?.school ?? '';
    form.place = academicRecord.value?.attributes?.place ?? '';
    form.from_level = academicRecord.value?.attributes?.fromLevel ?? '';
    form.to_level = academicRecord.value?.attributes?.toLevel ?? '';
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
            <div class="grid grid-cols-2 gap-3">
                <BaseInput
                    input-id="school"
                    :label="$tChoice('trans.school', 1)"
                    v-model="form.school"
                    placeholder="enter school/institution"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'school')"
                    :error="form.errors.school"
                />
                <BaseInput
                    input-id="place"
                    :label="$tChoice('trans.place', 1)"
                    v-model="form.place"
                    placeholder="enter place"
                    :label-uppercase="true"
                    :is-required="true"
                    @input="clearFormErrors(form, 'place')"
                    :error="form.errors.place"
                />
            </div>
        </template>
    </BaseModal>
</template>
