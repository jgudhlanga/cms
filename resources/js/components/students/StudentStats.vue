<script setup lang="ts">
import GenericButton from '@/components/core/button/GenericButton.vue';
import { useStudents } from '@/composables/students/useStudents';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudentFiltersState, StudentStats } from '@/types/students';
import { useDebounceFn } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    filters: StudentFiltersState;
    loading?: boolean;
    refreshKey?: number;
    showExportButton?: boolean;
    showResetButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    refreshKey: 0,
    showExportButton: false,
    showResetButton: true,
});

const emit = defineEmits<{
    (e: 'stats', value: StudentStats): void;
    (e: 'reset'): void;
}>();

const { openModal } = useModalStore();
const canExportStudents = computed(() => props.showExportButton && hasAbility('export:students'));

const openExportModal = (): void => {
    openModal(APP_MODULE_KEYS.student_list_export, { ...props.filters });
};

const { fetchStudentStats } = useStudents();

const stats = ref<StudentStats>({
    global: {
        total: 0,
        male: 0,
        female: 0,
        byLevel: [],
        byModeOfStudy: [],
        byStudentType: [],
    },
    filtered: {
        total: 0,
    },
});
const isLocalLoading = ref(false);

const normalizeFilters = (filters: StudentFiltersState): string =>
    JSON.stringify({
        search: filters.search?.trim() || undefined,
        name: filters.name?.trim() || undefined,
        department: filters.department?.length ? [...filters.department].sort((a, b) => a - b) : undefined,
        level: filters.level?.length ? [...filters.level].sort((a, b) => a - b) : undefined,
        course: filters.course?.length ? [...filters.course].sort((a, b) => a - b) : undefined,
        mode_of_study: filters.mode_of_study?.length ? [...filters.mode_of_study].sort((a, b) => a - b) : undefined,
        gender: filters.gender || undefined,
        student_type: filters.student_type || undefined,
        with_trashed: filters.with_trashed || undefined,
    });

const filterSignature = computed(() => normalizeFilters(props.filters));
const effectiveLoading = computed(() => props.loading || isLocalLoading.value);

const largestMode = computed(() => {
    const modes = stats.value.global.byModeOfStudy;
    if (!modes.length) {
        return null;
    }

    return modes.reduce((best, row) => (row.count > best.count ? row : best));
});

const loadStats = async () => {
    try {
        isLocalLoading.value = true;
        const res = await fetchStudentStats(props.filters);
        if (res) {
            stats.value = res;
            emit('stats', res);
        }
    } finally {
        isLocalLoading.value = false;
    }
};

const debouncedLoadStats = useDebounceFn(loadStats, 250);

onMounted(() => loadStats());
watch(filterSignature, (next, previous) => {
    if (next !== previous) {
        debouncedLoadStats();
    }
});
watch(() => props.refreshKey, () => loadStats());
</script>

<template>
    <div class="mb-1.5 transition-opacity" :class="{ 'pointer-events-none opacity-60': effectiveLoading }">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {{ $t('students.overview_read_only') }}
                </p>
                <div class="flex flex-wrap items-end gap-x-5 gap-y-1">
                    <div class="min-w-0">
                        <p class="text-sm leading-none font-bold tabular-nums text-indigo-700">
                            {{ stats.global.total.toLocaleString() }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">{{ $t('students.stat_total_students') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm leading-none font-bold tabular-nums">
                            <span class="text-sky-700">{{ stats.global.male.toLocaleString() }}</span>
                            <span class="font-normal text-muted-foreground"> / </span>
                            <span class="text-rose-600">{{ stats.global.female.toLocaleString() }}</span>
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">{{ $t('students.male_female') }}</p>
                    </div>
                    <div v-if="largestMode" class="min-w-0">
                        <p class="text-sm leading-none font-bold tabular-nums text-emerald-700">
                            {{ largestMode.count.toLocaleString() }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">
                            {{ $t('students.largest_mode_label', { name: largestMode.name }) }}
                        </p>
                    </div>
                </div>
            </div>
            <div
                v-if="showResetButton || canExportStudents"
                class="flex shrink-0 items-center gap-1.5"
            >
                <ResetButton
                    v-if="showResetButton"
                    class="!h-7 !rounded-md !px-2.5 !text-xs"
                    @click="emit('reset')"
                />
                <GenericButton
                    v-if="canExportStudents"
                    :icon="IconName.export"
                    :variant="ColorVariant.primary"
                    :title="$t('trans.export')"
                    class="!h-7 !rounded-md !px-2.5 !text-xs"
                    @click="openExportModal"
                />
            </div>
        </div>
    </div>
</template>
