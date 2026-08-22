<script setup lang="ts">
import { hasAbility } from '@/lib/permissions';
import { errorAlert } from '@/lib/alerts';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    classSize: string | number;
    editable?: boolean;
    departmentId?: string;
    intakePeriodId?: string | number;
    modeOfStudyId?: string | number;
    departmentCourseId?: string | number;
    departmentLevelId?: string | number;
}

const props = withDefaults(defineProps<Props>(), {
    editable: false,
});

const emit = defineEmits<{
    saved: [value: number];
}>();

const canEdit = computed(
    () =>
        props.editable &&
        hasAbility('department-setup:class-sizes') &&
        Boolean(props.departmentId && props.intakePeriodId && props.modeOfStudyId && props.departmentCourseId && props.departmentLevelId),
);

const editing = ref(false);
const draft = ref(Number(props.classSize) || 0);
const inputRef = ref<HTMLInputElement | null>(null);
const saving = ref(false);

watch(
    () => props.classSize,
    (value) => {
        if (!editing.value) {
            draft.value = Number(value) || 0;
        }
    },
);

const form = useForm({
    intake_period_id: '',
    mode_of_study_id: '',
    department_course_id: '',
    department_level_id: '',
    class_size: 0,
});

async function startEdit() {
    if (!canEdit.value || saving.value) {
        return;
    }
    draft.value = Number(props.classSize) || 0;
    editing.value = true;
    await nextTick();
    inputRef.value?.focus();
    inputRef.value?.select();
}

function cancelEdit() {
    editing.value = false;
    draft.value = Number(props.classSize) || 0;
}

function save() {
    if (!canEdit.value || saving.value || !editing.value) {
        return;
    }

    const nextSize = Math.max(0, Math.floor(Number(draft.value) || 0));
    if (nextSize === Number(props.classSize)) {
        editing.value = false;
        return;
    }

    form.intake_period_id = String(props.intakePeriodId);
    form.mode_of_study_id = String(props.modeOfStudyId);
    form.department_course_id = String(props.departmentCourseId);
    form.department_level_id = String(props.departmentLevelId);
    form.class_size = nextSize;
    saving.value = true;

    form.put(route('class-sizes.update', String(props.departmentId)), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
            emit('saved', nextSize);
        },
        onError: (errors) => {
            const messageText = Object.keys(errors).length
                ? Object.values(errors).join('\n')
                : 'Could not save intake limit';
            errorAlert(messageText);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter') {
        event.preventDefault();
        save();
    }
    if (event.key === 'Escape') {
        event.preventDefault();
        cancelEdit();
    }
}
</script>

<template>
    <div
        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium leading-5 text-green-700 uppercase"
        :class="canEdit ? (editing ? 'cursor-text' : 'cursor-pointer hover:bg-green-200/80') : ''"
        :style="canEdit && !editing ? { cursor: 'pointer' } : undefined"
        :title="canEdit && !editing ? $t('trans.ui_edit_intake_limit') : undefined"
        role="button"
        :tabindex="canEdit && !editing ? 0 : -1"
        @click="!editing && startEdit()"
        @keydown.enter.prevent="!editing && startEdit()"
    >
        <span>{{ $t('trans.ui_intake_limit') }}</span>
        <template v-if="editing">
            <input
                ref="inputRef"
                v-model.number="draft"
                type="number"
                min="0"
                step="1"
                class="h-5 w-12 cursor-text rounded border border-green-300 bg-white px-1 text-center text-[11px] font-bold text-green-800 tabular-nums outline-none focus:ring-1 focus:ring-green-400"
                :disabled="form.processing"
                @click.stop
                @keydown="onKeydown"
                @blur="save"
            />
        </template>
        <span v-else class="font-bold tabular-nums">{{ classSize }}</span>
    </div>
</template>
