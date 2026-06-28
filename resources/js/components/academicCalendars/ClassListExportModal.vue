<script setup lang="ts">
import ClassListExportClassPicker from '@/components/academicCalendars/ClassListExportClassPicker.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { successAlert } from '@/lib/alerts';
import { useModalStore } from '@/store/core/useModalStore';
import type { ClassListExportClassOption } from '@/types/academic-calendar';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    institutionDepartmentId: number;
    calendarYear: string;
    classConfigQuery: Record<string, string>;
    classes: ClassListExportClassOption[];
    singleClassId?: number | null;
}>();

const { closeModal, isOpen } = useModalStore();
const selectedClassIds = ref<number[]>([]);
const selectionError = ref('');

const exportableClassIds = computed(() =>
    props.classes
        .filter((classOption) => classOption.academicCalendarClassId != null)
        .map((classOption) => classOption.academicCalendarClassId as number),
);

const lockedSelection = computed(() => props.singleClassId != null);

const resetSelection = (): void => {
    if (props.singleClassId != null) {
        selectedClassIds.value = [props.singleClassId];
    } else {
        selectedClassIds.value = [...exportableClassIds.value];
    }
    selectionError.value = '';
};

watch(
    () => isOpen(APP_MODULE_KEYS.class_list_export),
    (open) => {
        if (open) {
            resetSelection();
        }
    },
);

const buildExportUrl = (): string => {
    if (props.singleClassId != null) {
        return route('academic-calendars.department-classes.class-list.export-class', {
            institution_department: String(props.institutionDepartmentId),
            calendar_year: props.calendarYear,
            academic_calendar_class: String(props.singleClassId),
        });
    }

    const params = new URLSearchParams({
        ...props.classConfigQuery,
    });

    selectedClassIds.value.forEach((classId) => {
        params.append('class_ids[]', String(classId));
    });

    const baseUrl = route('academic-calendars.department-classes.class-list.export', {
        institution_department: String(props.institutionDepartmentId),
        calendar_year: props.calendarYear,
    });

    const query = params.toString();

    return query !== '' ? `${baseUrl}?${query}` : baseUrl;
};

const handleExport = (): void => {
    if (selectedClassIds.value.length === 0) {
        selectionError.value = trans('academic_calendar.export_class_list_no_classes_selected');
        return;
    }

    window.open(buildExportUrl(), '_blank', 'noopener,noreferrer');
    successAlert(trans('academic_calendar.export_class_list_success'));
    closeModal(APP_MODULE_KEYS.class_list_export);
};
</script>

<template>
    <BaseModal
        :title="$t('academic_calendar.export_class_list_modal_title')"
        :name="APP_MODULE_KEYS.class_list_export"
        action-btn-text="academic_calendar.export_class_list_confirm"
        cancel-btn-text="trans.close"
        :on-form-action="handleExport"
        :size="SizeVariant.md"
    >
        <template #body>
            <div class="space-y-4">
                <p class="text-sm text-foreground">
                    {{ $t('academic_calendar.export_class_list_description') }}
                </p>
                <p v-if="selectionError" class="text-sm text-destructive">
                    {{ selectionError }}
                </p>
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase text-muted-foreground">
                        {{ $t('academic_calendar.export_class_list_select_classes') }}
                    </p>
                    <ClassListExportClassPicker
                        v-model:selected-class-ids="selectedClassIds"
                        :classes="classes"
                        :locked="lockedSelection"
                    />
                </div>
            </div>
        </template>
    </BaseModal>
</template>
