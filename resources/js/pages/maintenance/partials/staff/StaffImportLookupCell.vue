<script setup lang="ts">
import type {
    StaffImportLookupField,
    StaffImportLookupOption,
    StaffImportLookupType,
} from '@/types/staff-import';
import customAxios from '@/services/http-init';
import { trans } from 'laravel-vue-i18n';
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    field: StaffImportLookupField;
    options: StaffImportLookupOption[];
    modelValue: number | null;
    lookupType?: StaffImportLookupType;
    creatable?: boolean;
    isCreated?: boolean;
    compact?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [number | null];
    created: [StaffImportLookupOption];
}>();

const creating = ref(false);
const createError = ref<string | null>(null);

const selectedId = computed((): number | '' => {
    if (props.modelValue !== null) {
        return props.modelValue;
    }

    return props.field.resolvedId ?? '';
});

const isUnresolved = computed((): boolean => selectedId.value === '');

const selectClass = computed((): string => {
    if (props.isCreated || props.field.matchType === 'created') {
        return 'border-green-500 bg-green-50';
    }

    if (isUnresolved.value) {
        return 'border-destructive bg-destructive/5';
    }

    if (props.field.needsReview) {
        return 'border-amber-500 bg-amber-50';
    }

    return 'border-border';
});

const showCreateAction = computed((): boolean => {
    return Boolean(
        props.creatable
        && props.lookupType
        && isUnresolved.value
        && props.field.raw.trim() !== '',
    );
});

const onSelectChange = (event: Event): void => {
    const value = (event.target as HTMLSelectElement).value;

    emit('update:modelValue', value === '' ? null : Number(value));
};

const createLookup = async (): Promise<void> => {
    if (!props.lookupType || !props.field.raw.trim()) {
        return;
    }

    creating.value = true;
    createError.value = null;

    try {
        const response = await customAxios('').post<StaffImportLookupOption>(
            route('maintenance.staff-import.lookups.create'),
            {
                type: props.lookupType,
                name: props.field.raw.trim(),
            },
        );

        emit('created', response.data);
        emit('update:modelValue', response.data.value);
    } catch (caught) {
        const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
            .response?.data;

        createError.value =
            responseData?.errors?.name?.[0]
            ?? responseData?.message
            ?? trans('trans.maintenance_staff_import_preview_failed');
    } finally {
        creating.value = false;
    }
};
</script>

<template>
    <div :class="compact ? 'flex min-w-0 items-center gap-1.5' : 'min-w-[6rem] space-y-0.5'">
        <div class="relative min-w-0" :class="compact ? 'flex-1' : 'w-full'">
            <select
                class="h-7 w-full rounded border bg-background px-1.5 text-[10px]"
                :class="selectClass"
                :value="selectedId"
                @change="onSelectChange"
            >
                <option value="">{{ $t('trans.maintenance_staff_import_select_lookup') }}</option>
                <option v-for="option in options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <Check
                v-if="isCreated || field.matchType === 'created'"
                class="pointer-events-none absolute right-1 top-1/2 h-3 w-3 -translate-y-1/2 text-green-600"
            />
        </div>

        <button
            v-if="showCreateAction"
            type="button"
            class="shrink-0 text-[10px] text-primary hover:underline disabled:opacity-50"
            :class="compact ? 'whitespace-nowrap' : 'text-left'"
            :disabled="creating"
            @click="createLookup"
        >
            {{ $t('trans.maintenance_staff_import_create_lookup', { name: field.raw }) }}
        </button>

        <p
            v-if="!compact && (isCreated || field.matchType === 'created')"
            class="text-[10px] text-green-700"
        >
            {{ $t('trans.maintenance_staff_import_lookup_created') }}
        </p>

        <p v-if="createError" class="shrink-0 text-[10px] text-destructive">{{ createError }}</p>
    </div>
</template>
