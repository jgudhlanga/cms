<script setup lang="ts">
import { BaseCheckbox, BaseInputWithIcon } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { IconName } from '@/enums/icons';
import { SizeVariant } from '@/enums/sizes';
import type { AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    modalName: string;
    unassignedStudents: AcademicCalendarClassPreviewStudent[];
    onFormAction: () => void;
    onCloseModal: () => void;
}>();

const form = defineModel<InertiaForm<{ student_enrolment_ids: number[] }>>('form', { required: true });

const search = ref('');

const filteredStudents = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (term === '') {
        return props.unassignedStudents;
    }

    return props.unassignedStudents.filter((student) => {
        const haystack = `${student.name} ${student.studentNumber ?? ''} ${student.applicationTrackingNumber ?? ''}`.toLowerCase();

        return haystack.includes(term);
    });
});

const toggleStudent = (studentEnrolmentId: number): void => {
    const selected = form.value.student_enrolment_ids;
    if (selected.includes(studentEnrolmentId)) {
        form.value.student_enrolment_ids = selected.filter((id) => id !== studentEnrolmentId);

        return;
    }

    form.value.student_enrolment_ids = [...selected, studentEnrolmentId];
};
</script>

<template>
    <BaseModal
        :name="modalName"
        :title="$t('academic_calendar.add_students_modal_title')"
        :form="form"
        :on-form-action="onFormAction"
        :on-close-modal="onCloseModal"
        :size="SizeVariant.md"
        cancel-btn-text="trans.close"
        action-btn-text="academic_calendar.add_students_submit"
    >
        <template #body>
            <div class="flex flex-col gap-2">
                <BaseInputWithIcon
                    v-model="search"
                    :icon="IconName.search"
                    full-width
                    :placeholder="$t('academic_calendar.add_students_search_placeholder')"
                />
                <p v-if="form.errors.student_enrolment_ids" class="text-xs text-red-600">
                    {{ form.errors.student_enrolment_ids }}
                </p>
                <p v-if="unassignedStudents.length === 0" class="text-sm text-muted-foreground">
                    {{ $t('academic_calendar.add_students_none_available') }}
                </p>
                <div v-else class="max-h-72 overflow-y-auto rounded-md border border-border">
                    <button
                        v-for="student in filteredStudents"
                        :key="student.studentEnrolmentId"
                        type="button"
                        class="flex w-full items-center gap-2 border-b border-border/60 px-2.5 py-1.5 text-left last:border-b-0 hover:bg-muted/40"
                        @click="toggleStudent(student.studentEnrolmentId)"
                    >
                        <BaseCheckbox
                            :input-id="`add_student_${student.studentEnrolmentId}`"
                            :model-value="form.student_enrolment_ids.includes(student.studentEnrolmentId)"
                            :label="''"
                            class="pointer-events-none"
                        />
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ student.name }}</span>
                        <span class="shrink-0 text-[11px] text-muted-foreground">
                            {{ student.studentNumber ?? student.applicationTrackingNumber ?? '---' }}
                        </span>
                    </button>
                    <p v-if="filteredStudents.length === 0" class="px-2.5 py-3 text-sm text-muted-foreground">
                        {{ $t('trans.no_data') }}
                    </p>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
