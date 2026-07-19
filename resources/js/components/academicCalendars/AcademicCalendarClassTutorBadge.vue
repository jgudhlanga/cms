<script setup lang="ts">
import type { ClassTutorSummary } from '@/types/academic-calendar';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    tutor: ClassTutorSummary;
    canAssign: boolean;
    compact?: boolean;
}>();

const emit = defineEmits<{
    assign: [];
    remove: [];
}>();

const hasTutor = computed(() => props.tutor != null && props.tutor.name.trim() !== '');

const linkClass =
    'cursor-pointer font-medium text-primary underline-offset-4 decoration-primary/50 transition-colors duration-300 ease-out hover:underline';
</script>

<template>
    <div
        class="flex w-full items-center justify-between gap-x-2 gap-y-1"
        :class="compact ? 'text-xs' : 'text-sm'"
    >
        <template v-if="hasTutor">
            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                <span class="text-muted-foreground">{{ $t('academic_calendar.class_tutor') }}:</span>
                <span class="inline-flex min-w-0 items-center gap-1">
                    <button
                        v-if="canAssign"
                        type="button"
                        :class="linkClass"
                        class="truncate font-medium text-foreground"
                        @click.stop.prevent="emit('assign')"
                    >
                        {{ tutor?.name }}
                    </button>
                    <span v-else class="truncate font-medium text-foreground">{{ tutor?.name }}</span>
                    <button
                        v-if="canAssign"
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-full p-0.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                        :title="$t('academic_calendar.remove_tutor')"
                        :aria-label="$t('academic_calendar.remove_tutor')"
                        @click.stop.prevent="emit('remove')"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </span>
            </div>
            <button
                v-if="canAssign"
                type="button"
                :class="linkClass"
                class="shrink-0 text-[10px]"
                @click.stop.prevent="emit('assign')"
            >
                {{ $t('academic_calendar.change_tutor') }}
            </button>
        </template>
        <template v-else>
            <span class="text-muted-foreground">{{ $t('academic_calendar.no_tutor_assigned') }}</span>
            <button
                v-if="canAssign"
                type="button"
                :class="linkClass"
                class="shrink-0"
                @click.stop.prevent="emit('assign')"
            >
                {{ $t('academic_calendar.assign_tutor') }}
            </button>
        </template>
    </div>
</template>
