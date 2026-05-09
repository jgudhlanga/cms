<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { TextFieldType } from '@/enums/inputs';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { computed, defineEmits, ref, watch } from 'vue';

type Hostel = {
    id: number | string;
    name: string;
    warden_id?: number | string | null;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    description?: string | null;
};

interface Props {
    wardens: Array<{ id: number | string; name: string | null }>;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'saved'): void }>();

const hostel = ref<Hostel | null>(null);

const form = useForm({
    name: '',
    warden_id: null as null | string | number,
    location: '',
    floor_count: 0,
    rooms_count: 0,
    capacity: 0,
    status: 'active' as 'active' | 'inactive',
    type: null as null | 'male' | 'female' | 'mixed',
    description: '',
});

const { modals } = useModalStore();

const wardenOption = ref<SelectOption | null>(null);
const statusOption = ref<SelectOption | null>(null);
const typeOption = ref<SelectOption | null>(null);

const wardenOptions = computed<SelectOption[]>(() =>
    (props.wardens ?? [])
        .filter((w) => !!w?.id)
        .map((w) => ({ label: w.name ?? '---', value: String(w.id) }))
);

const statusOptions: SelectOption[] = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const typeOptions: SelectOption[] = [
    { label: 'Boys', value: 'male' },
    { label: 'Girls', value: 'female' },
    { label: 'Mixed', value: 'mixed' },
];

watch(modals!, () => {
    hostel.value = getModalEdit(APP_MODULE_KEYS.hostels) as Hostel | null;

    form.name = hostel.value?.name ?? '';
    form.warden_id = hostel.value?.warden_id ?? null;
    form.location = hostel.value?.location ?? '';
    form.floor_count = Number(hostel.value?.floor_count ?? 0);
    form.rooms_count = Number(hostel.value?.rooms_count ?? 0);
    form.capacity = Number(hostel.value?.capacity ?? 0);
    form.status = (hostel.value?.status ?? 'active') as 'active' | 'inactive';
    form.type = (hostel.value?.type ?? null) as any;
    form.description = hostel.value?.description ?? '';

    wardenOption.value =
        hostel.value?.warden_id != null
            ? {
                  label: props.wardens.find((w) => String(w.id) === String(hostel.value?.warden_id))?.name ?? '---',
                  value: String(hostel.value?.warden_id),
              }
            : null;
    statusOption.value = statusOptions.find((s) => s.value === form.status) ?? statusOptions[0];
    typeOption.value = form.type ? typeOptions.find((t) => t.value === form.type) ?? null : null;

    form.defaults();
});

const save = () => {
    form.warden_id = wardenOption.value?.value ? String(wardenOption.value.value) : null;
    form.status = (statusOption.value?.value as any) ?? 'active';
    form.type = (typeOption.value?.value as any) ?? null;

    const onSuccessAction = () => emit('saved');
    const options = buildFormOptions(form, 'Hostel saved', 'Failed to save hostel', APP_MODULE_KEYS.hostels, onSuccessAction);

    if (hostel.value?.id) {
        form.put(route('hostels.update', String(hostel.value.id)), { preserveScroll: true, ...options });
        return;
    }

    form.post(route('hostels.store'), { preserveScroll: true, ...options });
};
</script>

<template>
    <BaseModal :name="APP_MODULE_KEYS.hostels" :title="`${hostel ? 'Edit' : 'Create'} Hostel`" :on-form-action="() => save()" :form="form">
        <template #body>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                    input-id="name"
                    label="Name"
                    v-model="form.name"
                    :is-required="true"
                    :error="form.errors.name"
                    @input="clearFormErrors(form, 'name')"
                />

                <BaseSelect
                    label="Warden"
                    placeholder="Select warden (optional)"
                    v-model="wardenOption"
                    :options="wardenOptions"
                    :error="form.errors.warden_id"
                    @update:modelValue="clearFormErrors(form, 'warden_id')"
                />

                <BaseInput
                    input-id="location"
                    label="Location"
                    v-model="form.location"
                    :error="form.errors.location"
                    @input="clearFormErrors(form, 'location')"
                />

                <BaseSelect
                    label="Status"
                    v-model="statusOption"
                    :options="statusOptions"
                    :is-clearable="false"
                    :is-required="true"
                    :error="form.errors.status"
                    @update:modelValue="clearFormErrors(form, 'status')"
                />

                <BaseSelect
                    label="Type"
                    placeholder="Select type (optional)"
                    v-model="typeOption"
                    :options="typeOptions"
                    :error="form.errors.type"
                    @update:modelValue="clearFormErrors(form, 'type')"
                />

                <BaseInput
                    input-id="floor_count"
                    label="Floors"
                    :type="TextFieldType.number"
                    v-model="form.floor_count"
                    :is-required="true"
                    :error="form.errors.floor_count"
                    @input="clearFormErrors(form, 'floor_count')"
                />

                <BaseInput
                    input-id="rooms_count"
                    label="Rooms"
                    :type="TextFieldType.number"
                    v-model="form.rooms_count"
                    :is-required="true"
                    :error="form.errors.rooms_count"
                    @input="clearFormErrors(form, 'rooms_count')"
                />

                <BaseInput
                    input-id="capacity"
                    label="Capacity"
                    :type="TextFieldType.number"
                    v-model="form.capacity"
                    :is-required="true"
                    :error="form.errors.capacity"
                    @input="clearFormErrors(form, 'capacity')"
                />
            </div>

            <div class="mt-2">
                <Label :class="form.errors.description ? 'text-destructive' : ''">Description</Label>
                <RequiredIndicator class="hidden" />
                <Textarea v-model="form.description" class="mt-2" @input="clearFormErrors(form, 'description')" />
                <InputError class="mt-1 flex w-full lowercase" :message="form.errors.description" />
            </div>
        </template>
    </BaseModal>
</template>

