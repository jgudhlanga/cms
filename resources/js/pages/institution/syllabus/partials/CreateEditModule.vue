<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import Code from '@/components/core/form/text/Code.vue';
import { useCourseSyllabusModules } from '@/composables/institution/useCourseSyllabusModules';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { getModalEdit, getModalParent } from '@/lib/alerts';
import { useModalStore } from '@/store/core/useModalStore';
import { CourseSyllabusModule, CourseSyllabusModuleParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    courseSyllabusId: number;
    courseSyllabusTitle: string;
}

const props = defineProps<Props>();
const moduleRecord = ref<CourseSyllabusModule>();
const form = useForm<CourseSyllabusModuleParams>({
    course_syllabus_id: props.courseSyllabusId || null,
    title: '',
    code: '',
    duration_in_hours: null,
    nql_level: null,
    prerequisite_module_ids: [],
    shared: false,
});

const { modals } = useModalStore();
const { formSchema, saveCourseSyllabusModule } = useCourseSyllabusModules();

const selectedSyllabusTitle = computed(() => {
    const modalParent = getModalParent(APP_MODULE_KEYS.course_syllabus_modules);
    const parentCourseSyllabusId = Number(modalParent?.courseSyllabusId ?? props.courseSyllabusId);

    if (parentCourseSyllabusId !== props.courseSyllabusId) {
        return props.courseSyllabusTitle;
    }

    return props.courseSyllabusTitle;
});

watch(modals!, () => {
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
    form.defaults();
});

const save = () => {
    const parsed = formSchema().safeParse(form.data());
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
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <BaseInput input-id="course_syllabus" :label="$tChoice('syllabus.course_syllabus', 1)" :model-value="selectedSyllabusTitle" :disabled="true" />
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
                <label class="flex items-center gap-2 pt-6">
                    <input type="checkbox" v-model="form.shared" @change="clearFormErrors(form, 'shared')" />
                    <span>{{ $t('syllabus.shared') }}</span>
                </label>
            </div>
        </template>
    </BaseModal>
</template>
