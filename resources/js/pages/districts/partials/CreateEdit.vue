<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useDistricts } from '@/composables/districts/useDistricts';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { District, DistrictParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const district = ref<District>();
const form = useForm<DistrictParams>({
    name: '',
    description: ''
});

const { saveDistrict } = useDistricts();

const { modals } = useModalStore();

watch(modals!, () => {
    district.value = getModalEdit(APP_MODULE_KEYS.districts);
    form.name = district.value?.attributes?.name ?? '';
    form.description = district.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.districts"
        :title="`${district ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.district', 1)}`"
        :on-form-action="() => saveDistrict(form, district)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')"
                  :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')"
                         :error="form.errors.description" />
        </template>
    </BaseModal>
</template>
