<script setup lang="ts">
import HostelRoomComboSelect from '@/components/core/form/combobox/HostelRoomComboSelect.vue';
import { IconName, icons } from '@/lib/icons';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    form: InertiaForm<{ room: null }>;
    hostelId: string | number;
    modelValue: SelectOption;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: SelectOption];
}>();

const selectedRoom = computed({
    get: () => props.modelValue,
    set: (value: SelectOption) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="flex items-center">
        <div class="flex w-[200px] items-center space-x-2">
            <component :is="icons[IconName.room]" class="h-4 w-4 shrink-0 text-muted-foreground" />
            <span class="text-sm font-medium uppercase text-foreground">{{ $t('hms.ui_switch_room') }}</span>
        </div>
        <div class="flex w-1/3">
            <HostelRoomComboSelect
                :form="form"
                :hostel-id="hostelId"
                v-model="selectedRoom"
                label=""
                width-class="w-full"
            />
        </div>
    </div>
</template>
