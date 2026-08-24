<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Textarea } from '@/components/ui/textarea';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { InertiaForm } from '@inertiajs/vue3';
import { ColorVariant } from '@/enums/colors';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

export type ClassListActionPayload = {
    kind: 'add' | 'transition' | 'purge';
    applicationIds: number[];
    toType?: string;
    bypassRanking: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    requireNote: boolean;
    bypassWarning: string | null;
    confirmVariant: ColorVariant;
};

defineProps<{
    processing?: boolean;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    form?: InertiaForm<any>;
}>();

const emit = defineEmits<{
    confirm: [note: string];
    closed: [];
}>();

const { modals } = useModalStore();
const modalName = APP_MODULE_KEYS.class_list_action;

const note = ref('');
const noteError = ref<string | null>(null);
const action = ref<ClassListActionPayload | undefined>();

watch(
    modals!,
    () => {
        action.value = getModalEdit(modalName) as ClassListActionPayload | undefined;
        if (action.value) {
            note.value = '';
            noteError.value = null;
        }
    },
    { deep: true },
);

const needsNote = computed(() => action.value?.requireNote ?? true);

const validate = (): boolean => {
    if (!needsNote.value) {
        noteError.value = null;
        return true;
    }
    const trimmed = note.value.trim();
    if (trimmed.length < 10) {
        noteError.value = trans('trans.ui_purge_class_list_note_required');
        return false;
    }
    noteError.value = null;
    return true;
};

const handleConfirm = () => {
    if (!validate()) {
        return;
    }
    emit('confirm', note.value.trim());
};

const handleClose = () => {
    note.value = '';
    noteError.value = null;
    emit('closed');
};
</script>

<template>
    <BaseModal
        :name="modalName"
        :title="action?.title ?? ''"
        :has-form="true"
        :size="SizeVariant.xs"
        :esc-to-close="true"
        cancel-btn-text="trans.cancel"
        action-btn-text="trans.confirm"
        :show-action-button="true"
        :form="form"
        :on-form-action="handleConfirm"
        :on-close-modal="handleClose"
    >
        <template #body>
            <p class="text-sm text-muted-foreground">
                {{ action?.description }}
                <span class="font-semibold text-foreground">({{ action?.applicationIds?.length ?? 0 }})</span>
            </p>
            <div
                v-if="action?.bypassWarning"
                class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
            >
                {{ action.bypassWarning }}
            </div>
            <div v-if="needsNote" class="flex flex-col">
                <label class="mb-2.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $t('trans.reason') }}
                </label>
                <Textarea v-model="note" rows="4" :aria-invalid="Boolean(noteError)" :disabled="processing" />
                <p v-if="noteError" class="mt-2 text-xs text-destructive">{{ noteError }}</p>
            </div>
        </template>
    </BaseModal>
</template>
