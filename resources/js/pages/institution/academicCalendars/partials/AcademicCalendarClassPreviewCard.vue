<script setup lang="ts">
import type { AcademicCalendarClassPreview } from '@/types/academic-calendar';
import { UserIcon, UserRoundIcon, Users } from '@lucide/vue';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    classPreview: AcademicCalendarClassPreview;
    showUrl?: string | null;
}>();

const isSavedClass = computed(() => props.classPreview.academicCalendarClassId != null);
const isClickable = computed(() => props.showUrl != null && props.showUrl !== '');

const cardClass = computed(() => {
    const base = 'block overflow-hidden rounded-2xl border border-border bg-card shadow-md transition-all duration-200';

    return isClickable.value
        ? `${base} cursor-pointer hover:-translate-y-0.5 hover:shadow-lg`
        : `${base} cursor-default opacity-80`;
});
</script>

<template>
    <component :is="isClickable ? InertiaLink : 'div'" v-bind="isClickable ? { href: showUrl } : {}" :class="cardClass">
        <div class="h-0.5 bg-linear-to-r from-sky-400 to-blue-600" />

        <div class="p-5 sm:p-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold tracking-tight text-foreground">{{ classPreview.name }}</h2>
                <span
                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
                    :class="
                        isSavedClass
                            ? 'border-green-200 bg-green-50 text-green-700'
                            : 'border-border bg-muted text-muted-foreground'
                    "
                >
                    {{ isSavedClass ? $t('hms.status_active') : $t('trans.preview') }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2 rounded-xl bg-muted/60 p-2.5 text-center">
                <div class="flex flex-col items-center">
                    <Users class="h-4 w-4 text-muted-foreground" />
                    <span class="mt-1 text-xs text-muted-foreground">{{ $t('students.class_total') }}</span>
                    <span class="font-semibold text-foreground">{{ classPreview.studentCount }}</span>
                </div>
                <div class="flex flex-col items-center">
                    <UserIcon class="h-4 w-4 text-blue-600" />
                    <span class="mt-1 text-xs text-muted-foreground">{{ $tChoice('general.male', 2) }}</span>
                    <span class="font-semibold text-foreground">{{ classPreview.genderCounts?.male ?? 0 }}</span>
                </div>
                <div class="flex flex-col items-center">
                    <UserRoundIcon class="h-4 w-4 text-pink-600" />
                    <span class="mt-1 text-xs text-muted-foreground">{{ $tChoice('general.female', 2) }}</span>
                    <span class="font-semibold text-foreground">{{ classPreview.genderCounts?.female ?? 0 }}</span>
                </div>
            </div>
        </div>
    </component>
</template>
