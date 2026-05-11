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
import { trans } from 'laravel-vue-i18n';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Hostel } from '@/types/hms';
import { useHmsStore } from '@/store/hms/useHmsStore';


interface Props {
    wardens: Array<{ id: number | string; name: string | null }>;
}

const props = defineProps<Props>();

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
const hmsStore = useHmsStore();

const wardenOption = ref<string | null>(null);
const statusOption = ref<'active' | 'inactive'>('active');
const typeOption = ref<'male' | 'female' | 'mixed' | null>(null);

const wardenOptions = computed<SelectOption[]>(() =>
    (props.wardens ?? [])
        .filter((w) => !!w?.id)
        .map((w) => ({ label: w.name ?? '---', value: String(w.id) }))
);

const statusOptions = computed<SelectOption[]>(() => [
    { label: trans('hms.status_active'), value: 'active' },
    { label: trans('hms.status_inactive'), value: 'inactive' },
]);

const typeOptions = computed<SelectOption[]>(() => [
    { label: trans('hms.type_male'), value: 'male' },
    { label: trans('hms.type_female'), value: 'female' },
    { label: trans('hms.type_mixed'), value: 'mixed' },
]);

watch(modals!, () => {
    hostel.value = getModalEdit(APP_MODULE_KEYS.hostels) as Hostel | null;

    form.name = hostel.value?.attributes.name ?? '';
    form.warden_id = hostel.value?.attributes.wardenId ? String(hostel.value.attributes.wardenId) : null;
    form.location = hostel.value?.attributes.location ?? '';
    form.floor_count = Number(hostel.value?.attributes.floorCount ?? 0);
    form.rooms_count = Number(hostel.value?.attributes.roomsCount ?? 0);
    form.capacity = Number(hostel.value?.attributes.capacity ?? 0);
    form.status = (hostel.value?.attributes.status ?? 'active') as 'active' | 'inactive';
    form.type = (hostel.value?.attributes.type ?? null) as any;
    form.description = hostel.value?.attributes.description ?? '';

    wardenOption.value = form.warden_id != null ? String(form.warden_id) : null;
    statusOption.value = form.status;
    typeOption.value = form.type ?? null;

    form.defaults();
});

const save = () => {
    form.warden_id = wardenOption.value != null && String(wardenOption.value).trim() !== '' ? String(wardenOption.value) : null;
    form.status = statusOption.value ?? 'active';
    form.type = typeOption.value ?? null;

    if (!form.type) {
        form.setError('type', trans('hms.type_required'));
        return;
    }

    const onSuccessAction = () => {
        hmsStore.refreshHostels();
    };
    const options = buildFormOptions(form, trans('hms.hostel_saved'), trans('hms.hostel_save_failed'), APP_MODULE_KEYS.hostels, onSuccessAction);

    if (hostel.value?.id) {
        form.put(route('hostels.update', String(hostel.value.id)), { preserveScroll: true, ...options });
        return;
    }

    form.post(route('hostels.store'), { preserveScroll: true, ...options });
};
</script>

<template>
    <BaseModal :name="APP_MODULE_KEYS.hostels" :title="hostel ? $t('hms.edit_hostel') : $t('hms.create_hostel')" :on-form-action="() => save()" :form="form">
        <template #body>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                    input-id="name"
                    :label="$t('hms.name')"
                    v-model="form.name"
                    :is-required="true"
                    :error="form.errors.name"
                    @input="clearFormErrors(form, 'name')"
                />

                <BaseSelect
                    :label="$tChoice('hms.warden', 1)"
                    :placeholder="$t('hms.select_warden_optional')"
                    v-model="wardenOption"
                    :options="wardenOptions"
                    :error="form.errors.warden_id"
                    @update:modelValue="clearFormErrors(form, 'warden_id')"
                />

                <BaseInput
                    input-id="location"
                    :label="$tChoice('hms.location', 1)"
                    v-model="form.location"
                    :error="form.errors.location"
                    @input="clearFormErrors(form, 'location')"
                />

                <BaseSelect
                    :label="$tChoice('hms.status', 1)"
                    v-model="statusOption"
                    :options="statusOptions"
                    :is-clearable="false"
                    :is-required="true"
                    :error="form.errors.status"
                    @update:modelValue="clearFormErrors(form, 'status')"
                />

                <BaseSelect
                    :label="$tChoice('hms.type', 1)"
                    :placeholder="$t('hms.select_type')"
                    v-model="typeOption"
                    :options="typeOptions"
                    :is-required="true"
                    :error="form.errors.type"
                    @update:modelValue="clearFormErrors(form, 'type')"
                />

                <BaseInput
                    input-id="floor_count"
                    :label="$t('hms.floors')"
                    :type="TextFieldType.number"
                    v-model="form.floor_count"
                    :is-required="true"
                    :error="form.errors.floor_count"
                    @input="clearFormErrors(form, 'floor_count')"
                />

                <BaseInput
                    input-id="rooms_count"
                    :label="$t('hms.rooms')"
                    :type="TextFieldType.number"
                    v-model="form.rooms_count"
                    :is-required="true"
                    :error="form.errors.rooms_count"
                    @input="clearFormErrors(form, 'rooms_count')"
                />

                <BaseInput
                    input-id="capacity"
                    :label="$tChoice('hms.capacity', 1)"
                    :type="TextFieldType.number"
                    v-model="form.capacity"
                    :is-required="true"
                    :error="form.errors.capacity"
                    @input="clearFormErrors(form, 'capacity')"
                />
            </div>

            <div class="mt-2">
                <Label :class="form.errors.description ? 'text-destructive' : ''">{{ $t('hms.description') }}</Label>
                <RequiredIndicator class="hidden" />
                <Textarea v-model="form.description" class="mt-2" @input="clearFormErrors(form, 'description')" />
                <InputError class="mt-1 flex w-full lowercase" :message="form.errors.description" />
            </div>
        </template>
    </BaseModal>
</template>

