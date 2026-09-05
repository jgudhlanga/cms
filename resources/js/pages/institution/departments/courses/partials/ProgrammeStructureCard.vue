<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox, BaseInput } from '@/components/core/form';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentCourseLevel } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

type CalendarType = 'semester' | 'term' | 'abma';

interface ProgrammeSemester {
    id: number;
    position: number;
    name: string;
    kind: string;
}

interface Props {
    levelCourse: DepartmentCourseLevel & {
        durationYears?: number;
        taughtSemesterCount?: number;
        includesIndustrialAttachment?: boolean;
        attachmentSemesterCount?: number;
        programmeSemesters?: ProgrammeSemester[];
    };
}

const props = defineProps<Props>();
const canManage = hasAbility('manage:programme-structures');

const calendarType = computed((): CalendarType => props.levelCourse.calendarType ?? 'semester');

const periodsPerYear = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return 3;
        case 'abma':
            return 4;
        default:
            return 2;
    }
});

const taughtLabel = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_taught_terms');
        case 'abma':
            return trans('trans.ui_taught_abma_periods');
        default:
            return trans('trans.ui_taught_semesters');
    }
});

const attachmentLabel = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_attachment_terms');
        case 'abma':
            return trans('trans.ui_attachment_abma_periods');
        default:
            return trans('trans.ui_attachment_semesters');
    }
});

const taughtHint = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_programme_structure_taught_hint_term');
        case 'abma':
            return trans('trans.ui_programme_structure_taught_hint_abma');
        default:
            return trans('trans.ui_programme_structure_taught_hint_semester');
    }
});

const attachmentCountHint = computed(() => {
    switch (calendarType.value) {
        case 'term':
            return trans('trans.ui_programme_structure_attachment_count_hint_term');
        case 'abma':
            return trans('trans.ui_programme_structure_attachment_count_hint_abma');
        default:
            return trans('trans.ui_programme_structure_attachment_count_hint_semester');
    }
});

const form = useForm({
    duration_years: props.levelCourse.durationYears ?? 1,
    taught_semester_count: props.levelCourse.taughtSemesterCount ?? periodsPerYear.value,
    includes_industrial_attachment: props.levelCourse.includesIndustrialAttachment ?? false,
    attachment_semester_count: props.levelCourse.attachmentSemesterCount ?? periodsPerYear.value,
});

const programmeSemesters = computed(() => props.levelCourse.programmeSemesters ?? []);

const countNumber = (value: unknown, fallback: number): number => {
    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

const roundYears = (value: number): number => Math.round(value * 10) / 10;

const yearsLabel = (years: number): string => {
    const rounded = roundYears(years);

    if (rounded === 1) {
        return trans('trans.ui_programme_structure_one_year');
    }

    if (rounded === 1.5) {
        return trans('trans.ui_programme_structure_one_and_half_years');
    }

    return trans('trans.ui_programme_structure_years', {
        count: String(rounded),
    });
};

const taughtYears = computed(() =>
    roundYears(countNumber(form.taught_semester_count, periodsPerYear.value) / periodsPerYear.value),
);

const attachmentYears = computed(() =>
    roundYears(countNumber(form.attachment_semester_count, periodsPerYear.value) / periodsPerYear.value),
);

const durationHint = computed(() =>
    form.includes_industrial_attachment
        ? trans('trans.ui_programme_structure_duration_hint_with_attachment')
        : trans('trans.ui_programme_structure_duration_hint'),
);

const structureSummary = computed(() => {
    if (form.includes_industrial_attachment) {
        return trans('trans.ui_programme_structure_summary_with_attachment', {
            duration: yearsLabel(taughtYears.value + attachmentYears.value),
            taught: yearsLabel(taughtYears.value),
            attachment: yearsLabel(attachmentYears.value),
        });
    }

    return trans('trans.ui_programme_structure_summary', {
        duration: yearsLabel(countNumber(form.duration_years, taughtYears.value)),
    });
});

watch(
    () =>
        [
            countNumber(form.taught_semester_count, periodsPerYear.value),
            countNumber(form.attachment_semester_count, 0),
            Boolean(form.includes_industrial_attachment),
        ] as const,
    ([taughtCount, attachmentCount, included], previous) => {
        if (included) {
            const attachment = attachmentCount < 1 ? periodsPerYear.value : attachmentCount;

            if (attachmentCount < 1) {
                form.attachment_semester_count = attachment;
            }

            form.duration_years = roundYears((taughtCount + attachment) / periodsPerYear.value);

            return;
        }

        if (previous?.[2]) {
            form.duration_years = roundYears(taughtCount / periodsPerYear.value);
        }
    },
    { immediate: true },
);

const saveStructure = () => {
    form.post(route('department-level-courses.programme-structure.update', props.levelCourse.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <BaseCard>
        <div class="flex items-start justify-between gap-3">
            <HeadingSmall :title="`${levelCourse.level ?? ''} programme structure`" />
            <BaseButton v-if="canManage" :size="ButtonSize.sm" :processing="form.processing" :disabled="form.processing" @click="saveStructure">
                {{ trans('trans.save') }}
            </BaseButton>
        </div>
        <div v-if="canManage" class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
            <div>
                <BaseInput
                    v-model="form.duration_years"
                    :input-id="`duration_years_${levelCourse.id}`"
                    type="number"
                    min="0.5"
                    step="0.5"
                    :label="trans('trans.ui_programme_structure_duration_years')"
                    :vertical-layout="false"
                    :error="form.errors.duration_years"
                    :disabled="form.includes_industrial_attachment"
                    :readonly="form.includes_industrial_attachment"
                />
                <p class="text-muted-foreground mt-1 text-xs leading-relaxed">{{ durationHint }}</p>
            </div>
            <div>
                <BaseInput
                    v-model="form.taught_semester_count"
                    :input-id="`taught_semester_count_${levelCourse.id}`"
                    type="number"
                    min="1"
                    :label="taughtLabel"
                    :vertical-layout="false"
                    :error="form.errors.taught_semester_count"
                />
                <p class="text-muted-foreground mt-1 text-xs leading-relaxed">{{ taughtHint }}</p>
            </div>
            <div class="sm:col-span-2">
                <BaseCheckbox
                    v-model="form.includes_industrial_attachment"
                    :input-id="`includes_attachment_${levelCourse.id}`"
                    :label="trans('trans.ui_includes_industrial_attachment')"
                />
                <p class="text-muted-foreground mt-1 text-xs leading-relaxed">
                    {{ trans('trans.ui_programme_structure_attachment_hint') }}
                </p>
            </div>
            <div v-if="form.includes_industrial_attachment">
                <BaseInput
                    v-model="form.attachment_semester_count"
                    :input-id="`attachment_semester_count_${levelCourse.id}`"
                    type="number"
                    min="1"
                    :label="attachmentLabel"
                    :vertical-layout="false"
                    :error="form.errors.attachment_semester_count"
                />
                <p class="text-muted-foreground mt-1 text-xs leading-relaxed">{{ attachmentCountHint }}</p>
            </div>
        </div>
        <p v-if="canManage" class="text-muted-foreground text-sm leading-relaxed">{{ structureSummary }}</p>
        <div v-if="programmeSemesters.length" class="flex flex-wrap gap-1.5">
            <span
                v-for="semester in programmeSemesters"
                :key="semester.id"
                class="rounded-full px-2.5 py-0.5 text-xs"
                :class="
                    semester.kind === 'industrial_attachment'
                        ? 'bg-primary/10 text-primary'
                        : 'bg-muted text-muted-foreground'
                "
            >
                {{ semester.name }}
            </span>
        </div>
    </BaseCard>
</template>
