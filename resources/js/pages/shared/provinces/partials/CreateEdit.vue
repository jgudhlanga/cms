<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useProvinces } from '@/composables/shared/useProvinces';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Province, ProvinceParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const province = ref<Province>();
const form = useForm<ProvinceParams>({
    title: '',
});

const { saveProvince } = useProvinces();

const { modals } = useModalStore();

watch(modals!, () => {
    province.value = getModalEdit(APP_MODULE_KEYS.provinces);
    form.title = province.value?.attributes?.title ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.provinces"
        :title="`${province ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.province', 1)}`"
        :on-form-action="() => saveProvince(form, province)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
        </template>
    </BaseModal>
</template>
