<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import { TypeVariant } from '@/enums/type-variants';
import { hasAbility } from '@/lib/permissions';
import HttpService from '@/services/http.service';
import type { Student } from '@/types/students';
import { computed, onMounted, reactive, ref, watch } from 'vue';

type ClearanceSection = {
    key: string;
    label: string;
    help: string;
    cleared: boolean;
    notes: string | null;
    clearedBy: string | null;
    clearedAt: string | null;
    canEdit: boolean;
};

type ClearancePayload = {
    id: number | null;
    sections: ClearanceSection[];
    isFullyCleared: boolean;
};

type IdentityPayload = {
    isZimbabwean: boolean;
    idNumber: string | null;
    passportNumber: string | null;
    studentNumber: string | null;
};

const props = defineProps<{
    student: Student;
}>();

const loading = ref(false);
const saving = ref(false);
const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const clearance = ref<ClearancePayload | null>(null);
const identity = ref<IdentityPayload | null>(null);
const allowOnlineClearance = ref(true);
const calendarType = ref<'semester' | 'term' | 'abma'>('semester');
const semesters = ref<Array<{ id: number; label: string }>>([]);
const selected = reactive({
    calendarYear: '' as string,
    semesterId: null as number | null,
});
const drafts = ref<Record<string, { cleared: boolean; notes: string }>>({});
const suppressWatch = ref(false);

const canViewTab = () =>
    hasAbility([
        'student-clearance:accounts',
        'student-clearance:library',
        'student-clearance:security',
        'student-clearance:hostel',
        'student-clearance:department',
    ]);

const editableSections = computed(() =>
    (clearance.value?.sections ?? []).filter((section) => section.canEdit),
);

const parsedCalendarYear = computed(() => {
    const year = Number.parseInt(String(selected.calendarYear).trim(), 10);
    return Number.isFinite(year) && year >= 2000 && year <= 2100 ? year : null;
});

const periodChoiceKey = computed(() => {
    if (calendarType.value === 'term') {
        return 'academic_calendar.term';
    }
    if (calendarType.value === 'abma') {
        return 'academic_calendar.abma';
    }
    return 'academic_calendar.semester';
});

const hydrateDrafts = (sections: ClearanceSection[]) => {
    const next: Record<string, { cleared: boolean; notes: string }> = {};
    sections.forEach((section) => {
        next[section.key] = {
            cleared: section.cleared,
            notes: section.notes ?? '',
        };
    });
    drafts.value = next;
};

const fetchClearance = async () => {
    if (!props.student?.id) {
        return;
    }

    loading.value = true;
    errorMessage.value = null;

    try {
        const data = await HttpService.get(route('v1.students.clearance.show', props.student.id), {
            params: {
                calendar_year: parsedCalendarYear.value ?? undefined,
                semester_id: selected.semesterId ?? undefined,
            },
        });

        suppressWatch.value = true;
        semesters.value = data.data.options.semesters ?? [];
        selected.calendarYear = data.data.defaults.calendarYear != null
            ? String(data.data.defaults.calendarYear)
            : selected.calendarYear;
        selected.semesterId = data.data.defaults.semesterId;
        calendarType.value = data.data.calendarType ?? 'semester';
        allowOnlineClearance.value = data.data.allowOnlineClearance ?? true;
        identity.value = data.data.identity;
        clearance.value = data.data.clearance;
        if (data.data.clearance?.sections) {
            hydrateDrafts(data.data.clearance.sections);
        } else {
            drafts.value = {};
        }
    } catch (error: any) {
        errorMessage.value = error?.response?.data?.message ?? 'Unable to load clearance.';
    } finally {
        loading.value = false;
        suppressWatch.value = false;
    }
};

const saveEditableSections = async () => {
    if (!props.student?.id || !parsedCalendarYear.value || !selected.semesterId) {
        return;
    }

    const sections = editableSections.value
        .map((section) => {
            const draft = drafts.value[section.key];
            if (!draft) {
                return null;
            }

            return {
                section: section.key,
                cleared: draft.cleared,
                notes: draft.notes || null,
            };
        })
        .filter((row): row is { section: string; cleared: boolean; notes: string | null } => row !== null);

    if (sections.length === 0) {
        return;
    }

    saving.value = true;
    errorMessage.value = null;
    successMessage.value = null;

    try {
        const data = await HttpService.put(route('v1.students.clearance.update', props.student.id), {
            calendar_year: parsedCalendarYear.value,
            semester_id: selected.semesterId,
            sections,
        });

        clearance.value = data.data;
        hydrateDrafts(data.data.sections);
        successMessage.value = data.message;
    } catch (error: any) {
        const sectionErrors = error?.response?.data?.errors;
        const firstSectionNoteError = sectionErrors
            ? Object.values(sectionErrors).flat()?.[0]
            : null;

        errorMessage.value =
            (typeof firstSectionNoteError === 'string' ? firstSectionNoteError : null)
            ?? error?.response?.data?.errors?.calendar_year?.[0]
            ?? error?.response?.data?.errors?.notes?.[0]
            ?? error?.response?.data?.message
            ?? 'Unable to save clearance.';
    } finally {
        saving.value = false;
    }
};

onMounted(fetchClearance);

watch(
    () => [selected.calendarYear, selected.semesterId] as const,
    ([calendarYear, semesterId], previous) => {
        if (suppressWatch.value || !previous) {
            return;
        }

        const [prevYear, prevSemesterId] = previous;
        if (calendarYear === prevYear && semesterId === prevSemesterId) {
            return;
        }

        const year = Number.parseInt(String(calendarYear).trim(), 10);
        const yearValid = Number.isFinite(year) && year >= 2000 && year <= 2100;

        if (calendarYear !== prevYear) {
            if (!yearValid) {
                clearance.value = null;
                return;
            }
            if (semesterId) {
                fetchClearance();
            }
            return;
        }

        if (yearValid && semesterId) {
            fetchClearance();
        }
    },
);
</script>

<template>
    <div v-if="!canViewTab()" class="p-4">
        <BaseAlert :type="TypeVariant.danger" :description="$t('trans.forbidden_message')" />
    </div>
    <div v-else class="space-y-4 p-2">
        <div class="grid gap-3 sm:grid-cols-2">
            <BaseInput
                input-id="clearance_calendar_year"
                :label="$tChoice('academic_calendar.calendar_year', 1)"
                v-model="selected.calendarYear"
                inputmode="numeric"
                placeholder="2026"
            />
            <label class="text-sm space-y-1">
                <span class="font-medium">{{ $tChoice(periodChoiceKey, 1) }}</span>
                <select
                    v-model.number="selected.semesterId"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    :disabled="!parsedCalendarYear"
                >
                    <option :value="null" disabled>{{ $tChoice(periodChoiceKey, 1) }}</option>
                    <option v-for="semester in semesters" :key="semester.id" :value="semester.id">
                        {{ semester.label }}
                    </option>
                </select>
            </label>
        </div>

        <div v-if="identity" class="rounded-lg border border-border p-3 text-sm space-y-1">
            <p v-if="identity.isZimbabwean">
                <span class="font-medium">{{ $t('trans.id_number') }}:</span>
                {{ identity.idNumber || '—' }}
            </p>
            <p v-else>
                <span class="font-medium">{{ $t('trans.passport_number') }}:</span>
                {{ identity.passportNumber || '—' }}
            </p>
            <p>
                <span class="font-medium">{{ $tChoice('trans.student_number', 1) }}:</span>
                {{ identity.studentNumber || '—' }}
            </p>
        </div>

        <BaseAlert v-if="errorMessage" :type="TypeVariant.danger" :description="errorMessage" />
        <BaseAlert v-if="successMessage" :type="TypeVariant.success" :description="successMessage" />
        <BaseAlert
            v-if="!allowOnlineClearance"
            :type="TypeVariant.info"
            :description="$t('trans.clearance_accounts_only_mode_help')"
        />
        <p v-if="loading" class="text-sm text-muted-foreground">Loading…</p>

        <div v-if="clearance" class="space-y-3">
            <div
                v-for="section in clearance.sections"
                :key="section.key"
                class="rounded-lg border border-border p-4 space-y-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">{{ section.label }}</h3>
                        <p class="text-sm text-muted-foreground">{{ section.help }}</p>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="section.cleared ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
                    >
                        {{ section.cleared ? $t('trans.clearance_cleared') : $t('trans.clearance_pending') }}
                    </span>
                </div>

                <template v-if="section.canEdit && drafts[section.key]">
                    <BaseSwitch
                        :input-id="`clearance_${section.key}`"
                        v-model="drafts[section.key].cleared"
                        :label="drafts[section.key].cleared ? $t('trans.clearance_mark_cleared') : $t('trans.clearance_mark_uncleared')"
                        :on-update="(value) => (drafts[section.key].cleared = value)"
                    />
                    <BaseInput
                        :input-id="`clearance_notes_${section.key}`"
                        :label="$t('trans.clearance_notes')"
                        v-model="drafts[section.key].notes"
                    />
                </template>

                <div v-else class="text-sm text-muted-foreground space-y-1">
                    <p v-if="section.notes"><span class="font-medium">{{ $t('trans.clearance_notes') }}:</span> {{ section.notes }}</p>
                    <p v-if="section.clearedBy"><span class="font-medium">{{ $t('trans.clearance_cleared_by') }}:</span> {{ section.clearedBy }}</p>
                    <p v-if="section.clearedAt"><span class="font-medium">{{ $t('trans.clearance_cleared_at') }}:</span> {{ section.clearedAt }}</p>
                </div>
            </div>

            <div v-if="editableSections.length > 0" class="flex justify-end pt-2">
                <BaseButton
                    type="button"
                    :processing="saving"
                    :disabled="saving || !parsedCalendarYear || !selected.semesterId"
                    @click="saveEditableSections"
                >
                    {{ $t('trans.save') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
