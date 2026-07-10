<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { TextFieldType } from '@/enums/inputs';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelAmenity } from '@/types/hms';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

const form = useForm({
    name: '',
    market_value: null as number | null,
});

const { modals } = useModalStore();
const hmsStore = useHmsStore();

const amenity = ref<HostelAmenity | null>(null);

watch(modals!, () => {
    amenity.value = (getModalEdit(APP_MODULE_KEYS.hostel_amenities) as HostelAmenity | null) ?? null;

    form.name = amenity.value?.attributes.name ?? '';
    form.market_value = amenity.value?.attributes.marketValue ?? null;
    form.defaults();
    form.clearErrors();
});

const save = () => {
    if (!form.name.trim()) {
        form.setError('name', trans('hms.amenity_name_required'));
        return;
    }

    const options = buildFormOptions(
        form,
        trans('hms.amenity_saved'),
        trans('hms.amenity_save_failed'),
        APP_MODULE_KEYS.hostel_amenities,
        () => hmsStore.refreshAmenities(),
    );

    if (amenity.value?.id) {
        form.put(route('hostel-amenities.update', String(amenity.value.id)), { preserveScroll: true, ...options });
        return;
    }

    form.post(route('hostel-amenities.store'), { preserveScroll: true, ...options });
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_amenities"
        :title="amenity ? $t('hms.edit_amenity') : $t('hms.create_amenity')"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4">
                <BaseInput
                    input-id="amenity_name"
                    :label="$t('hms.amenity_name')"
                    :type="TextFieldType.text"
                    v-model="form.name"
                    :is-required="true"
                    :error="form.errors.name"
                    @input="clearFormErrors(form, 'name')"
                />
                <BaseInput
                    input-id="amenity_market_value"
                    :label="$t('hms.market_value')"
                    :type="TextFieldType.number"
                    v-model="form.market_value"
                    :error="form.errors.market_value"
                    @input="clearFormErrors(form, 'market_value')"
                />
            </div>
        </template>
    </BaseModal>
</template>
