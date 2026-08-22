<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { Textarea } from '@/components/ui/textarea';
import { ColorVariant } from '@/enums/colors';
import { trans } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    count: number;
    processing?: boolean;
}>();

const emit = defineEmits<{
    closed: [];
    confirm: [note: string];
}>();

const note = ref('');
const noteError = ref<string | null>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            note.value = '';
            noteError.value = null;
        }
    },
);

const validate = (): boolean => {
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
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-3">
        <div class="absolute inset-0 bg-black/50" @click="emit('closed')" />
        <div class="relative z-10 w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
            <h3 class="mb-2 text-sm font-semibold uppercase">{{ $t('trans.ui_remove_from_class_list') }}</h3>
            <p class="mb-3 text-sm text-muted-foreground">
                {{ $t('trans.ui_purge_class_list_confirm') }}
                <span class="font-semibold text-foreground">({{ count }})</span>
            </p>
            <div class="mb-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                <strong>{{ $t('trans.ui_note') }}</strong>
                {{ $t('trans.ui_purge_class_list_confirm') }}
            </div>
            <label class="mb-1 block text-xs font-medium uppercase text-muted-foreground">
                {{ $t('trans.ui_purge_class_list_note_label') }}
            </label>
            <Textarea v-model="note" rows="4" class="mb-1" :aria-invalid="Boolean(noteError)" />
            <p v-if="noteError" class="mb-3 text-xs text-destructive">{{ noteError }}</p>
            <div class="mt-4 flex justify-end gap-2">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    :title="$t('trans.cancel')"
                    classes="rounded-full"
                    :disabled="processing"
                    @click="emit('closed')"
                />
                <BaseButton
                    type="button"
                    :variant="ColorVariant.warning"
                    :title="$t('trans.ui_remove_from_class_list')"
                    classes="rounded-full"
                    :disabled="processing"
                    @click="handleConfirm"
                />
            </div>
        </div>
    </div>
</template>
