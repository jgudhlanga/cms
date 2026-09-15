<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useAcademicStaffOptions } from '@/composables/institution/useAcademicStaffOptions';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ref, toRef, watch } from 'vue';

const props = defineProps<{
    departmentId: number | string;
    calendarYear: string;
    classConfigId: number | string;
    current: { staffId: number; name: string } | null;
}>();

const { options, isLoading } = useAcademicStaffOptions(toRef(props, 'departmentId'));
const selected = ref<SelectOption | null>(null);
const form = useForm<{ staff_id: number | null }>({ staff_id: null });

const { modals } = useModalStore();

watch(modals!, () => {
    selected.value = props.current ? { value: props.current.staffId, label: props.current.name } : null;
    form.staff_id = props.current?.staffId ?? null;
    form.clearErrors();
});

watch(selected, (option) => {
    form.staff_id = option?.value != null && option.value !== '' ? Number(option.value) : null;
    clearFormErrors(form, 'staff_id');
});

const save = () => {
    const removing = form.staff_id === null;

    form.patch(
        route('academic-calendars.department-classes.lecturer-in-charge', {
            institution_department: String(props.departmentId),
            calendar_year: props.calendarYear,
            class_config: String(props.classConfigId),
        }),
        buildFormOptions(
            form,
            trans(removing ? 'academic_calendar.lecturer_in_charge_removed' : 'academic_calendar.lecturer_in_charge_assigned'),
            trans('trans.item_save_failure', { item: trans('academic_calendar.lecturer_in_charge') }),
            APP_MODULE_KEYS.lecturer_in_charge_assign,
        ),
    );
};

const remove = () => {
    selected.value = null;
    form.staff_id = null;
    save();
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.lecturer_in_charge_assign"
        :title="current ? $t('academic_calendar.lecturer_in_charge_change_action') : $t('academic_calendar.lecturer_in_charge_assign_action')"
        :on-form-action="() => save()"
        :form="form"
        :size="SizeVariant.md"
    >
        <template #body>
            <div class="space-y-3">
                <p id="lecturer-in-charge-hint" class="text-sm text-muted-foreground">
                    {{ $t('academic_calendar.lecturer_in_charge_hint') }}
                </p>
                <BaseCombobox
                    :label="$t('academic_calendar.lecturer_in_charge')"
                    v-model="selected"
                    :options="options"
                    :is-loading="isLoading"
                    :is-required="true"
                    :error="form.errors.staff_id"
                    list-class="z-[70]"
                />
                <button
                    v-if="current"
                    type="button"
                    class="rounded text-sm text-destructive hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    @click="remove"
                >
                    {{ $t('academic_calendar.lecturer_in_charge_remove_action') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>
