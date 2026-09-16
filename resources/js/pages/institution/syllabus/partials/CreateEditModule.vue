<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import SelectLecturerSelect from '@/components/core/form/select/SelectLecturerSelect.vue';
import Code from '@/components/core/form/text/Code.vue';
import { useSemestersByCalendarType } from '@/composables/academicCalendars/useSemestersByCalendarType';
import { useCourseSyllabusModules } from '@/composables/institution/useCourseSyllabusModules';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { getModalEdit, getModalParent } from '@/lib/alerts';
import { useModalStore } from '@/store/core/useModalStore';
import { CourseSyllabusModule, CourseSyllabusModuleParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { SizeVariant } from '@/enums/sizes';

type ProgrammeSemesterOption = {
    id: number;
    name: string;
    position: number;
    periodInYear?: number | null;
    kind?: string;
};

interface Props {
    courseSyllabusId: number;
    courseSyllabusTitle: string;
    institutionDepartmentId: number;
    calendarType?: 'term' | 'semester' | 'abma' | null;
    programmeSemesters?: ProgrammeSemesterOption[];
}

const props = defineProps<Props>();
const moduleRecord = ref<CourseSyllabusModule>();
const form = useForm<CourseSyllabusModuleParams>({
    course_syllabus_id: props.courseSyllabusId || null,
    semester_id: null,
    programme_semester_id: null,
    title: '',
    code: '',
    duration_in_hours: null,
    nql_level: null,
    prerequisite_module_ids: [],
    shared: false,
    all_semesters: false,
    capture_mark_only: false,
    staff_ids: [],
});

const { modals } = useModalStore();
const { formSchema, saveCourseSyllabusModule } = useCourseSyllabusModules();
const { yearOptions, yearOptionsLoading, loadYearOptions } = useSemestersByCalendarType();

const resolvedCalendarType = computed(() => props.calendarType ?? 'semester');

const programmeSemesterOptions = computed(() =>
    (props.programmeSemesters ?? []).map((semester) => ({
        value: String(semester.id),
        label: semester.name,
        periodInYear: semester.periodInYear ?? null,
    })),
);

const hasProgrammeSemesters = computed(() => programmeSemesterOptions.value.length > 0);

const selectedSyllabusTitle = computed(() => {
    const modalParent = getModalParent(APP_MODULE_KEYS.course_syllabus_modules);
    const parentCourseSyllabusId = Number(modalParent?.courseSyllabusId ?? props.courseSyllabusId);

    if (parentCourseSyllabusId !== props.courseSyllabusId) {
        return props.courseSyllabusTitle;
    }

    return props.courseSyllabusTitle;
});

const syncSemesterFromProgrammeSemester = (programmeSemesterId: string | number | null) => {
    if (programmeSemesterId === null || programmeSemesterId === '') {
        return;
    }

    const selected = programmeSemesterOptions.value.find((option) => option.value === String(programmeSemesterId));
    if (!selected) {
        return;
    }

    const periodInYear = selected.periodInYear ?? 1;
    const calendarMatch = yearOptions.value.find((option) => {
        const label = String(option.label ?? '').toLowerCase();
        return label.includes(String(periodInYear));
    });

    if (calendarMatch) {
        form.semester_id = calendarMatch.value;
    } else if (yearOptions.value.length > 0) {
        const index = Math.max(0, Math.min(yearOptions.value.length - 1, periodInYear - 1));
        form.semester_id = yearOptions.value[index]?.value ?? yearOptions.value[0].value;
    }
};

watch(modals!, async () => {
    moduleRecord.value = getModalEdit(APP_MODULE_KEYS.course_syllabus_modules);
    const modalParent = getModalParent(APP_MODULE_KEYS.course_syllabus_modules);
    const parentCourseSyllabusId = Number(modalParent?.courseSyllabusId ?? props.courseSyllabusId);

    form.course_syllabus_id = parentCourseSyllabusId || null;
    form.title = moduleRecord.value?.attributes?.title ?? '';
    form.code = moduleRecord.value?.attributes?.code ?? '';
    form.duration_in_hours = moduleRecord.value?.attributes?.durationInHours ?? null;
    form.nql_level = moduleRecord.value?.attributes?.nqlLevel ?? null;
    form.prerequisite_module_ids = moduleRecord.value?.attributes?.prerequisiteModuleIds ?? [];
    form.shared = moduleRecord.value?.attributes?.shared ?? false;
    form.all_semesters = moduleRecord.value?.attributes?.allSemesters ?? false;
    form.capture_mark_only = moduleRecord.value?.attributes?.captureMarkOnly ?? false;
    form.staff_ids = moduleRecord.value?.attributes?.staffIds ?? [];

    await loadYearOptions(resolvedCalendarType.value);

    if (hasProgrammeSemesters.value) {
        const preferredProgramme =
            moduleRecord.value?.attributes?.programmeSemesterId != null
                ? String(moduleRecord.value.attributes.programmeSemesterId)
                : null;

        if (preferredProgramme !== null && programmeSemesterOptions.value.some((o) => o.value === preferredProgramme)) {
            form.programme_semester_id = preferredProgramme;
        } else if (programmeSemesterOptions.value.length > 0) {
            form.programme_semester_id = programmeSemesterOptions.value[0].value;
        } else {
            form.programme_semester_id = null;
        }

        syncSemesterFromProgrammeSemester(form.programme_semester_id);
    } else {
        form.programme_semester_id = null;
        const preferred =
            moduleRecord.value?.attributes?.semesterId != null
                ? String(moduleRecord.value.attributes.semesterId)
                : null;
        if (preferred !== null && yearOptions.value.some((o) => o.value === preferred)) {
            form.semester_id = preferred;
        } else if (yearOptions.value.length > 0) {
            form.semester_id = yearOptions.value[0].value;
        } else {
            form.semester_id = null;
        }
    }

    form.defaults();
});

const onProgrammeSemesterChange = (value: string | number | null) => {
    form.programme_semester_id = value;
    clearFormErrors(form, 'programme_semester_id');
    syncSemesterFromProgrammeSemester(value);
    clearFormErrors(form, 'semester_id');
};

const save = () => {
    const parsed = formSchema(hasProgrammeSemesters.value).safeParse(form.data());
    if (!parsed.success) {
        const fieldErrors = parsed.error.flatten().fieldErrors;
        Object.entries(fieldErrors).forEach(([field, errors]) => {
            if (errors?.length) {
                form.setError(field as keyof CourseSyllabusModuleParams, errors[0]);
            }
        });
        return;
    }

    saveCourseSyllabusModule(form, moduleRecord.value?.id);
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.course_syllabus_modules"
        :title="`${moduleRecord ? $t('trans.update') : $t('trans.create')} ${$tChoice('syllabus.module', 1)}`"
        :on-form-action="save"
        :form="form"
        :size="SizeVariant.lg"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <BaseInput input-id="course_syllabus" :label="$tChoice('syllabus.course_syllabus', 1)" :model-value="selectedSyllabusTitle" :disabled="true" />
                <BaseSelect
                    v-if="hasProgrammeSemesters"
                    class="w-full"
                    :label="$tChoice('syllabus.semester', 1)"
                    placeholder=""
                    :options="programmeSemesterOptions"
                    :loading="false"
                    v-model="form.programme_semester_id"
                    :is-searchable="false"
                    :is-required="true"
                    @update:modelValue="onProgrammeSemesterChange"
                    :error="form.errors.programme_semester_id"
                />
                <BaseSelect
                    v-else
                    class="w-full"
                    :label="$tChoice('syllabus.semester', 1)"
                    placeholder=""
                    :options="yearOptions"
                    :loading="yearOptionsLoading"
                    v-model="form.semester_id"
                    :is-searchable="false"
                    :is-required="true"
                    @update:modelValue="clearFormErrors(form, 'semester_id')"
                    :error="form.errors.semester_id"
                />
                <BaseInput
                    input-id="title"
                    :label="$tChoice('trans.title', 1)"
                    v-model="form.title"
                    :is-required="true"
                    :error="form.errors.title"
                    @input="clearFormErrors(form, 'title')"
                />
                <Code v-model="form.code" :error="form.errors.code" :is-required="true" @input="clearFormErrors(form, 'code')" />
                <BaseInput
                    input-id="duration_in_hours"
                    :label="$t('syllabus.duration_in_hours')"
                    v-model="form.duration_in_hours"
                    type="number"
                    :error="form.errors.duration_in_hours"
                    @input="clearFormErrors(form, 'duration_in_hours')"
                />
                <BaseInput
                    input-id="nql_level"
                    :label="$t('syllabus.nql_level')"
                    v-model="form.nql_level"
                    type="number"
                    :error="form.errors.nql_level"
                    @input="clearFormErrors(form, 'nql_level')"
                />
                <SelectLecturerSelect
                    v-model="form.staff_ids"
                    :institution-department-id="institutionDepartmentId"
                    :form="form"
                    :error="form.errors.staff_ids"
                />
                <label class="flex items-center gap-2 pt-6">
                    <input type="checkbox" v-model="form.shared" @change="clearFormErrors(form, 'shared')" />
                    <span>{{ $t('syllabus.shared') }}</span>
                </label>
                <div class="pt-6">
                    <BaseSwitch
                        input-id="all_semesters"
                        v-model="form.all_semesters"
                        :label="$t('syllabus.all_semesters')"
                        :on-update="(value: boolean) => { form.all_semesters = value; clearFormErrors(form, 'all_semesters'); }"
                    />
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('syllabus.all_semesters_hint') }}
                    </p>
                </div>
                <div class="pt-6">
                    <BaseSwitch
                        input-id="capture_mark_only"
                        v-model="form.capture_mark_only"
                        :label="$t('syllabus.capture_mark_only')"
                        :on-update="(value: boolean) => { form.capture_mark_only = value; clearFormErrors(form, 'capture_mark_only'); }"
                    />
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('syllabus.capture_mark_only_hint') }}
                    </p>
                </div>
            </div>
        </template>
    </BaseModal>
</template>
