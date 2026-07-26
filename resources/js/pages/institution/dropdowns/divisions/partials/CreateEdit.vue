<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useDivisions } from '@/composables/institution/useDivisions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Division, DivisionParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    staffOptions: Array<{ id: number | string; name: string | null }>;
}>();

const division = ref<Division>();
const form = useForm<DivisionParams>({
    name: '',
    description: '',
    head_of_division_id: null,
});
const headOfDivisionOption = ref<SelectOption | null>(null);

const { saveDivision } = useDivisions();
const { modals } = useModalStore();

const staffSelectOptions = computed<SelectOption[]>(() =>
    (props.staffOptions ?? []).map((staff) => ({
        value: Number(staff.id),
        label: staff.name ?? '—',
    })),
);

watch(modals!, () => {
    division.value = getModalEdit(APP_MODULE_KEYS.divisions);
    form.name = division.value?.attributes?.name ?? '';
    form.description = division.value?.attributes?.description ?? '';
    form.head_of_division_id = division.value?.attributes?.headOfDivisionId
        ? Number(division.value.attributes.headOfDivisionId)
        : null;
    headOfDivisionOption.value =
        form.head_of_division_id != null
            ? (staffSelectOptions.value.find((option) => Number(option.value) === Number(form.head_of_division_id)) ?? null)
            : null;
    form.defaults();
});

watch(headOfDivisionOption, (option) => {
    form.head_of_division_id = option?.value != null ? Number(option.value) : null;
    clearFormErrors(form, 'head_of_division_id');
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.divisions"
        :title="`${division ? $t('trans.edit') : $t('trans.create')} ${$tChoice('trans.division', 1)}`"
        :on-form-action="() => saveDivision(form, division)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
            <BaseCombobox
                v-model="headOfDivisionOption"
                :label="$t('trans.head_of_division')"
                :placeholder="$t('trans.select_head_of_division')"
                :options="staffSelectOptions"
                :error="form.errors.head_of_division_id"
            />
        </template>
    </BaseModal>
</template>
