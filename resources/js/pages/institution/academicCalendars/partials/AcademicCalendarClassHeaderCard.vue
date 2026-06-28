<script setup lang="ts">
import AcademicCalendarClassLecturerBadge from '@/components/academicCalendars/AcademicCalendarClassLecturerBadge.vue';
import { IconButton } from '@/components/core/button';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import type { ClassLecturerSummary } from '@/types/academic-calendar';

defineProps<{
    title: string;
    description: string | null;
    studentCount: number;
    lecturer?: ClassLecturerSummary;
    canUpdate: boolean;
    canExportClassList?: boolean;
    canAssignLecturer?: boolean;
}>();

const emit = defineEmits<{
    edit: [];
    exportClassList: [];
    assignLecturer: [];
}>();
</script>

<template>
    <BaseCard>
        <div class="flex items-start justify-between gap-2">
            <HeadingSmall :title="title" />
            <div class="flex flex-wrap items-center gap-2">
                <BaseButton
                    v-if="canExportClassList"
                    type="button"
                    :title="$t('academic_calendar.export_class_list')"
                    classes="rounded-full"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.sm"
                    @click="emit('exportClassList')"
                />
                <IconButton v-if="canUpdate" :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="emit('edit')" />
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <LabelValue :label="$tChoice('trans.student', 2)" :value="String(studentCount)" />
            <LabelValue :label="$t('academic_calendar.description')" :value="description ?? '---'" />
            <div v-if="canAssignLecturer" class="md:col-span-3">
                <AcademicCalendarClassLecturerBadge
                    :lecturer="lecturer ?? null"
                    :can-assign="canAssignLecturer"
                    @assign="emit('assignLecturer')"
                />
            </div>
        </div>
    </BaseCard>
</template>
