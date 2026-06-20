<script setup lang="ts">
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useSponsorTypes } from '@/composables/shared/useSponsorTypes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { SponsorType, SponsorTypeParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const sponsorType = ref<SponsorType>();
const form = useForm<SponsorTypeParams>({
    name: '',
});

const { saveSponsorType } = useSponsorTypes();

const { modals } = useModalStore();

watch(modals!, () => {
    sponsorType.value = getModalEdit(APP_MODULE_KEYS.sponsor_types);
    form.name = sponsorType.value?.attributes?.name ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.sponsor_types"
        :title="`${sponsorType ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.sponsor_type', 1)}`"
        :on-form-action="() => saveSponsorType(form, sponsorType)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
        </template>
    </BaseModal>
</template>
