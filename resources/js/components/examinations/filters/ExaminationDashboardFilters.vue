<script setup lang="ts">
import ResetButton from '@/components/core/button/ResetButton.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import type {
    ExaminationDashboardFiltersState,
    ExaminationFilterOptions,
} from '@/types/examinations';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { X } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    filters: ExaminationDashboardFiltersState;
    filterOptions: ExaminationFilterOptions;
}

type ActiveTag = {
    id: string;
    label: string;
    clear: () => void;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: ExaminationDashboardFiltersState): void;
}>();

const sessionModel = ref<SelectOption | null>(null);
const disciplineModel = ref<SelectOption | null>(null);
const subjectModel = ref<SelectOption | null>(null);
const compareSessionModel = ref<SelectOption | null>(null);
const isSyncingFromProps = ref(false);

const toOption = (value: string | null | undefined, options: SelectOption[]): SelectOption | null => {
    if (!value) {
        return null;
    }

    return options.find((option) => String(option.value) === String(value)) ?? null;
};

const defaultFilters = (): ExaminationDashboardFiltersState => ({
    session: props.filterOptions.sessions[0]?.value
        ? String(props.filterOptions.sessions[0].value)
        : null,
    discipline: null,
    subject_code: null,
    compare_session: null,
});

const currentFilters = (): ExaminationDashboardFiltersState => ({
    session: sessionModel.value?.value ? String(sessionModel.value.value) : null,
    discipline: disciplineModel.value?.value ? String(disciplineModel.value.value) : null,
    subject_code: subjectModel.value?.value ? String(subjectModel.value.value) : null,
    compare_session: compareSessionModel.value?.value ? String(compareSessionModel.value.value) : null,
});

const filtersMatch = (
    left: ExaminationDashboardFiltersState,
    right: ExaminationDashboardFiltersState,
): boolean =>
    (left.session ?? null) === (right.session ?? null)
    && (left.discipline ?? null) === (right.discipline ?? null)
    && (left.subject_code ?? null) === (right.subject_code ?? null)
    && (left.compare_session ?? null) === (right.compare_session ?? null);

const isDefaultState = computed(() => filtersMatch(currentFilters(), defaultFilters()));

const syncFromProps = (): void => {
    isSyncingFromProps.value = true;

    sessionModel.value = toOption(props.filters.session, props.filterOptions.sessions);
    disciplineModel.value = toOption(props.filters.discipline, props.filterOptions.disciplines);
    subjectModel.value = toOption(props.filters.subject_code, props.filterOptions.subjects);
    compareSessionModel.value = toOption(
        props.filters.compare_session,
        props.filterOptions.compareSessions ?? [],
    );

    void nextTick(() => {
        isSyncingFromProps.value = false;
    });
};

const applyLocalFilters = (next: ExaminationDashboardFiltersState): void => {
    isSyncingFromProps.value = true;
    sessionModel.value = toOption(next.session, props.filterOptions.sessions);
    disciplineModel.value = toOption(next.discipline, props.filterOptions.disciplines);
    subjectModel.value = toOption(next.subject_code, props.filterOptions.subjects);
    compareSessionModel.value = toOption(
        next.compare_session,
        props.filterOptions.compareSessions ?? [],
    );
    void nextTick(() => {
        isSyncingFromProps.value = false;
    });
};

const emitChange = (next: ExaminationDashboardFiltersState): void => {
    emit('change', next);
};

const resetFilters = (): void => {
    const next = defaultFilters();
    applyLocalFilters(next);
    emitChange(next);
};

const clearField = (partial: Partial<ExaminationDashboardFiltersState>): void => {
    const next: ExaminationDashboardFiltersState = {
        ...currentFilters(),
        ...partial,
    };
    applyLocalFilters(next);
    emitChange(next);
};

const activeTags = computed<ActiveTag[]>(() => {
    const tags: ActiveTag[] = [];
    const defaults = defaultFilters();
    const current = currentFilters();

    if (current.session && current.session !== defaults.session && sessionModel.value) {
        tags.push({
            id: 'session',
            label: String(sessionModel.value.label),
            clear: () =>
                clearField({
                    session: defaults.session,
                    discipline: null,
                    subject_code: null,
                    compare_session: null,
                }),
        });
    }

    if (current.discipline && disciplineModel.value) {
        tags.push({
            id: 'discipline',
            label: String(disciplineModel.value.label),
            clear: () => clearField({ discipline: null, subject_code: null }),
        });
    }

    if (current.subject_code && subjectModel.value) {
        tags.push({
            id: 'subject',
            label: String(subjectModel.value.label),
            clear: () => clearField({ subject_code: null }),
        });
    }

    if (current.compare_session && compareSessionModel.value) {
        tags.push({
            id: 'compare_session',
            label: String(compareSessionModel.value.label),
            clear: () => clearField({ compare_session: null }),
        });
    }

    return tags;
});

syncFromProps();

watch(
    () => [props.filters, props.filterOptions],
    () => {
        syncFromProps();
    },
    { deep: true },
);

const emitFilters = useDebounceFn((): void => {
    if (isSyncingFromProps.value) {
        return;
    }

    const next = currentFilters();

    if (filtersMatch(next, props.filters)) {
        return;
    }

    emitChange(next);
}, 400);

watch(sessionModel, () => {
    if (!isSyncingFromProps.value) {
        disciplineModel.value = null;
        subjectModel.value = null;
        if (
            compareSessionModel.value?.value
            && String(compareSessionModel.value.value) === String(sessionModel.value?.value ?? '')
        ) {
            compareSessionModel.value = null;
        }
    }
    emitFilters();
});

watch(disciplineModel, () => {
    if (!isSyncingFromProps.value) {
        subjectModel.value = null;
    }
    emitFilters();
});

watch(subjectModel, emitFilters);
watch(compareSessionModel, emitFilters);

defineExpose({ resetFilters });
</script>

<template>
    <div class="rounded-lg border border-border bg-muted/40 p-2" role="group" :aria-label="$tChoice('trans.filter', 2)">
        <div class="mb-1.5 flex items-center justify-between gap-2">
            <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $tChoice('trans.filter', 2) }}
            </p>
            <ResetButton
                class="!h-7 !rounded-md !px-2.5 !text-xs"
                :disabled="isDefaultState"
                @click="resetFilters"
            />
        </div>

        <div class="flex flex-col gap-1.5">
            <div class="grid min-w-0 grid-cols-1 gap-1.5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="sessionModel"
                        :options="filterOptions.sessions"
                        :placeholder="$t('examinations.session')"
                        width-class="w-full"
                        class="rounded-md"
                    />
                </div>
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="disciplineModel"
                        :options="filterOptions.disciplines"
                        :placeholder="$t('examinations.discipline')"
                        width-class="w-full"
                        class="rounded-md"
                    />
                </div>
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="subjectModel"
                        :options="filterOptions.subjects"
                        :placeholder="$t('examinations.module_subject')"
                        width-class="w-full"
                        class="rounded-md"
                    />
                </div>
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="compareSessionModel"
                        :options="filterOptions.compareSessions ?? []"
                        :placeholder="$t('examinations.compare_session_placeholder')"
                        width-class="w-full"
                        class="rounded-md"
                    />
                </div>
            </div>

            <div v-if="activeTags.length" class="flex min-w-0 flex-wrap items-center gap-1 pt-0.5">
                <button
                    v-for="tag in activeTags"
                    :key="tag.id"
                    type="button"
                    class="inline-flex h-5 max-w-full items-center gap-0.5 rounded-md border border-border bg-muted px-1.5 text-[10px] font-medium text-foreground"
                    @click="tag.clear()"
                >
                    <span class="truncate">{{ tag.label }}</span>
                    <X class="h-2.5 w-2.5 shrink-0 opacity-70" />
                </button>
            </div>
        </div>
    </div>
</template>
