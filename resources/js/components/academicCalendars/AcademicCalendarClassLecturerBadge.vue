<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { ButtonSize } from '@/enums/buttons';
import { computed } from 'vue';

export type ClassLecturerSummary = {
    id: number;
    name: string;
} | null;

const props = defineProps<{
    lecturer: ClassLecturerSummary;
    canAssign: boolean;
    compact?: boolean;
}>();

const emit = defineEmits<{
    assign: [];
}>();

const hasLecturer = computed(() => props.lecturer != null && props.lecturer.name.trim() !== '');
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" :class="compact ? 'text-sm' : ''">
        <span v-if="hasLecturer" class="text-foreground">
            <span class="text-muted-foreground">{{ $t('academic_calendar.class_lecturer') }}:</span>
            <span class="font-medium">{{ lecturer?.name }}</span>
        </span>
        <span v-else class="text-muted-foreground">{{ $t('academic_calendar.no_lecturer_assigned') }}</span>
        <BaseButton
            v-if="canAssign"
            type="button"
            :title="hasLecturer ? $t('academic_calendar.change_lecturer') : $t('academic_calendar.assign_lecturer')"
            :variant="ColorVariant.primary_outline"
            :size="compact ? ButtonSize.sm : ButtonSize.md"
            classes="rounded-full"
            @click="emit('assign')"
        />
    </div>
</template>
