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
import type { HostelRoom } from '@/types/hms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import HostelSelect from '@/components/core/form/select/HostelSelect.vue';

const room = ref<HostelRoom | null>(null);

const form = useForm({
    hostel_id:    null as null | string | number,
    name:         '' as string,
    room_type:    'double' as 'single' | 'double' | 'triple' | 'suite',
    capacity:     1,
    status:       'vacant' as 'vacant' | 'occupied' | 'maintenance',
    max_occupancy: 1,
    floor_number: null as null | number,
    description:  '',
});

const { modals } = useModalStore();
const hmsStore = useHmsStore();

const hostelOption    = ref<string | null>(null);
const roomTypeOption  = ref<'single' | 'double' | 'triple' | 'suite'>('double');
const statusOption    = ref<'vacant' | 'occupied' | 'maintenance'>('vacant');


const roomTypeOptions = computed<SelectOption[]>(() => [
    { label: trans('hms.room_type_single'), value: 'single' },
    { label: trans('hms.room_type_double'), value: 'double' },
    { label: trans('hms.room_type_triple'), value: 'triple' },
    { label: trans('hms.room_type_suite'),  value: 'suite'  },
]);

const statusOptions = computed<SelectOption[]>(() => [
    { label: trans('hms.room_status_vacant'),      value: 'vacant'      },
    { label: trans('hms.room_status_occupied'),    value: 'occupied'    },
    { label: trans('hms.room_status_maintenance'), value: 'maintenance' },
]);

watch(modals!, () => {
    room.value = getModalEdit(APP_MODULE_KEYS.hostel_rooms) as HostelRoom | null;

    form.hostel_id    = room.value?.attributes.hostelId ? String(room.value.attributes.hostelId) : null;
    form.name         = room.value?.attributes.name ?? '';
    form.room_type    = (room.value?.attributes.roomType ?? 'single') as typeof form.room_type;
    form.capacity     = Number(room.value?.attributes.capacity ?? 1);
    form.status       = (room.value?.attributes.status ?? 'vacant') as typeof form.status;
    form.max_occupancy = Number(room.value?.attributes.maxOccupancy ?? 1);
    form.floor_number = room.value?.attributes.floorNumber ?? null;
    form.description  = room.value?.attributes.description ?? '';

    hostelOption.value   = form.hostel_id != null ? String(form.hostel_id) : null;
    roomTypeOption.value = form.room_type;
    statusOption.value   = form.status;

    form.defaults();
});

const save = () => {
    // sync select values back into form
    form.hostel_id  = hostelOption.value != null && String(hostelOption.value).trim() !== '' ? String(hostelOption.value) : null;
    form.room_type  = roomTypeOption.value;
    form.status     = statusOption.value;

    // front-end required-field guards
    if (!form.hostel_id) {
        form.setError('hostel_id', trans('hms.hostel_required'));
        return;
    }
    if (!form.name || !form.name.trim()) {
        form.setError('name', trans('hms.room_name_required'));
        return;
    }
    if (!form.room_type) {
        form.setError('room_type', trans('hms.room_type_required'));
        return;
    }
    if (!form.max_occupancy || form.max_occupancy < 1) {
        form.setError('max_occupancy', trans('hms.max_occupancy_required'));
        return;
    }
    if (form.floor_number === null || form.floor_number === undefined || isNaN(Number(form.floor_number))) {
        form.setError('floor_number', trans('hms.floor_number_required'));
        return;
    }

    const onSuccessAction = () => {
        hmsStore.refreshRooms();
    };
    const options = buildFormOptions(form, trans('hms.room_saved'), trans('hms.room_save_failed'), APP_MODULE_KEYS.hostel_rooms, onSuccessAction);

    if (room.value?.id) {
        form.put(route('hostel-rooms.update', String(room.value.id)), { preserveScroll: true, ...options });
        return;
    }

    form.post(route('hostel-rooms.store'), { preserveScroll: true, ...options });
};
</script>

<template>
    <BaseModal :name="APP_MODULE_KEYS.hostel_rooms" :title="room ? $t('hms.edit_room') : $t('hms.create_room')" :on-form-action="() => save()" :form="form">
        <template #body>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Hostel -->
                <HostelSelect
                    v-model="hostelOption"
                    :is-required="true"
                    :error="form.errors.hostel_id"
                    @update:modelValue="clearFormErrors(form, 'hostel_id')"
                />

                 <!-- Floor Number (optional) -->
                <BaseInput
                    input-id="floor_number"
                    :label="$t('hms.floor_number')"
                    :type="TextFieldType.number"
                    v-model="form.floor_number"
                    :is-required="true"
                    :error="form.errors.floor_number"
                    @input="clearFormErrors(form, 'floor_number')"
                />

                <!-- Room Name -->
                <BaseInput
                    input-id="name"
                    :label="$t('hms.room_name')"
                    :type="TextFieldType.text"
                    v-model="form.name"
                    :is-required="true"
                    :error="form.errors.name"
                    @input="clearFormErrors(form, 'name')"
                />

                <!-- Room Type -->
                <BaseSelect
                    :label="$tChoice('hms.room_type', 1)"
                    :placeholder="$t('hms.select_room_type')"
                    v-model="roomTypeOption"
                    :options="roomTypeOptions"
                    :is-clearable="false"
                    :is-required="true"
                    :error="form.errors.room_type"
                    @update:modelValue="clearFormErrors(form, 'room_type')"
                />

                <!-- Status -->
                <BaseSelect
                    :label="$tChoice('hms.status', 1)"
                    :placeholder="$t('hms.select_room_status')"
                    v-model="statusOption"
                    :options="statusOptions"
                    :is-clearable="false"
                    :is-required="true"
                    :error="form.errors.status"
                    @update:modelValue="clearFormErrors(form, 'status')"
                />

                <!-- Capacity -->
                <BaseInput
                    input-id="capacity"
                    :label="$tChoice('hms.capacity', 1)"
                    :type="TextFieldType.number"
                    v-model="form.capacity"
                    :is-required="true"
                    :error="form.errors.capacity"
                    @input="clearFormErrors(form, 'capacity')"
                />

                <!-- Max Occupancy -->
                <BaseInput
                    input-id="max_occupancy"
                    :label="$t('hms.max_occupancy')"
                    :type="TextFieldType.number"
                    v-model="form.max_occupancy"
                    :is-required="true"
                    :error="form.errors.max_occupancy"
                    @input="clearFormErrors(form, 'max_occupancy')"
                />

            </div>

            <!-- Description (optional) -->
            <div class="mt-2">
                <Label :class="form.errors.description ? 'text-destructive' : ''">{{ $t('hms.description') }}</Label>
                <RequiredIndicator class="hidden" />
                <Textarea v-model="form.description" class="mt-2" @input="clearFormErrors(form, 'description')" />
                <InputError class="mt-1 flex w-full lowercase" :message="form.errors.description" />
            </div>
        </template>
    </BaseModal>
</template>
