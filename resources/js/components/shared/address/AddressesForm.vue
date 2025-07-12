<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import Address1 from '@/components/core/form/text/Address1.vue';
import Address2 from '@/components/core/form/text/Address2.vue';
import Address3 from '@/components/core/form/text/Address3.vue';
import Address4 from '@/components/core/form/text/Address4.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useAddresses } from '@/composables/shared/useAddresses';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { Address, AddressParams } from '@/types/shared';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    postUrl: string;
}

const props = defineProps<Props>();

const { isItTrue } = useUtils();
const address = ref<Address>();
const form = useForm<AddressParams>({
    address_1: '',
    address_2: '',
    address_3: '',
    address_4: '',
    address_5: '',
    address_6: '',
    address_is_main: false,
});

const { updateAddress, createAddress } = useAddresses();

const { modals } = useModalStore();

watch(modals!, () => {
    address.value = getModalEdit(APP_MODULE_KEYS.addresses);
    form.address_1 = address.value?.attributes?.address1 ?? '';
    form.address_2 = address.value?.attributes?.address2 ?? '';
    form.address_3 = address.value?.attributes?.address3 ?? '';
    form.address_4 = address.value?.attributes?.address4 ?? '';
    form.address_5 = address.value?.attributes?.address5 ?? '';
    form.address_6 = address.value?.attributes?.address6 ?? '';
    form.address_is_main = isItTrue(address.value?.attributes?.addressIsMain) ?? false;
    form.defaults();
});

const save = () => {
    if (Number(address.value?.id?.toString()) > 0) {
        updateAddress(form, address.value);
    } else {
        createAddress(form, props.postUrl);
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.addresses"
        :title="`${address ? $t('trans.update') : $t('trans.create')} ${$tChoice('trans.address', 1)}`"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-3">
                <Address1
                    v-model="form.address_1"
                    @input="clearFormErrors(form, 'address_1')"
                    :error="form.errors.address_1"
                    :is-required="true"
                    :label-uppercase="true"
                />
                <Address2
                    v-model="form.address_2"
                    @input="clearFormErrors(form, 'address_2')"
                    :error="form.errors.address_2"
                    :is-required="true"
                    :label-uppercase="true"
                />
                <Address3
                    v-model="form.address_3"
                    @input="clearFormErrors(form, 'address_3')"
                    :error="form.errors.address_3"
                    :is-required="true"
                    :label-uppercase="true"
                />
                <Address4
                    v-model="form.address_4"
                    @input="clearFormErrors(form, 'address_4')"
                    :error="form.errors.address_4"
                    :label-uppercase="true"
                />
                <BaseCheckbox input-id="address_is_main" v-model="form.address_is_main" :label="$t('trans.main')" :label-uppercase="true" />
            </div>
        </template>
    </BaseModal>
</template>
