<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import RelationshipComboSelect from '@/components/core/form/combobox/RelationshipComboSelect.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { clearFormErrors } from '@/lib/forms';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';

const {
    next_of_kin_name,
    next_of_kin_phone_number,
    relationship,
    next_of_kin_address_1,
    next_of_kin_address_2,
    next_of_kin_address_3,
    next_of_kin_address_4,
    address_1,
    address_2,
    address_3,
    address_4,
} = storeToRefs(useCreateApplicationFormStore());

const props = withDefaults(
    defineProps<{
        form: InertiaForm<CreateApplicationParams>;
        bare?: boolean;
    }>(),
    {
        bare: false,
    },
);
const { form } = props;

const sameAsContactAddress = ref(false);
const userOverrodeToggle = ref(false);

const normalizeAddress = (value: string | null | undefined): string => (value ?? '').trim().toLowerCase();

const hasContactAddress = (): boolean =>
    Boolean(normalizeAddress(address_1.value) && normalizeAddress(address_2.value) && normalizeAddress(address_3.value));

const addressesMatch = (): boolean =>
    normalizeAddress(next_of_kin_address_1.value) === normalizeAddress(address_1.value) &&
    normalizeAddress(next_of_kin_address_2.value) === normalizeAddress(address_2.value) &&
    normalizeAddress(next_of_kin_address_3.value) === normalizeAddress(address_3.value) &&
    normalizeAddress(next_of_kin_address_4.value) === normalizeAddress(address_4.value);

const copyContactAddress = () => {
    next_of_kin_address_1.value = address_1.value ?? '';
    next_of_kin_address_2.value = address_2.value ?? '';
    next_of_kin_address_3.value = address_3.value ?? '';
    next_of_kin_address_4.value = address_4.value ?? '';
    clearFormErrors(form, 'next_of_kin_address_1');
    clearFormErrors(form, 'next_of_kin_address_2');
    clearFormErrors(form, 'next_of_kin_address_3');
};

watch(sameAsContactAddress, (checked, wasChecked) => {
    if (checked) {
        userOverrodeToggle.value = false;
        copyContactAddress();
    } else if (wasChecked) {
        userOverrodeToggle.value = true;
    }
});

const syncSameAsContactToggle = () => {
    if (!addressesMatch()) {
        userOverrodeToggle.value = false;
        return;
    }

    if (!userOverrodeToggle.value && hasContactAddress()) {
        sameAsContactAddress.value = true;
    }
};

watch(
    [address_1, address_2, address_3, address_4, next_of_kin_address_1, next_of_kin_address_2, next_of_kin_address_3, next_of_kin_address_4],
    () => {
        syncSameAsContactToggle();
        if (sameAsContactAddress.value) {
            copyContactAddress();
        }
    },
);

onMounted(() => {
    syncSameAsContactToggle();
});
</script>

<template>
    <component
        :is="bare ? 'div' : BaseCard"
        :class="bare ? 'space-y-6' : undefined"
        :title="bare ? undefined : $t('trans.next_of_kin')"
        :description="bare ? undefined : $t('trans.next_of_kin_description')"
    >
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <BaseInput
                input-id="next_of_kin_name"
                :label="$tChoice('trans.name', 1)"
                v-model="next_of_kin_name"
                :placeholder="$t('trans.ui_enter_next_of_kin')"
                :is-required="true"
                @input="clearFormErrors(form, 'next_of_kin_name')"
                :error="form.errors.next_of_kin_name"
            />
            <BaseInput
                input-id="next_of_kin_phone_number"
                :label="$t('trans.phone_number')"
                v-model="next_of_kin_phone_number"
                :placeholder="$t('trans.ui_enter_phone_number')"
                :is-required="true"
                @input="clearFormErrors(form, 'next_of_kin_phone_number')"
                :error="form.errors.next_of_kin_phone_number"
            />
            <div class="sm:col-span-2">
                <RelationshipComboSelect
                    :form="form"
                    v-model="relationship"
                    data-field="relationship"
                    :error="form.errors.relationship"
                    :is-required="true"
                />
            </div>
        </div>
        <div class="flex flex-col">
            <HeadingSmall :title="$t('trans.residential_address')" :description="$t('trans.residential_address_description')" class="mt-5" />
        </div>
        <BaseSwitch
            input-id="same_as_contact_address"
            v-model="sameAsContactAddress"
            class="mt-3"
            :label="$t('trans.same_as_contact_address')"
            :on-update="(value) => (sameAsContactAddress = value)"
        />
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <BaseInput
                input-id="next_of_kin_address_1"
                :label="$t('trans.address_house_number')"
                v-model="next_of_kin_address_1"
                :placeholder="$t('trans.ui_eg_house_number')"
                :disabled="sameAsContactAddress"
                @input="clearFormErrors(form, 'next_of_kin_address_1')"
                :error="form.errors.next_of_kin_address_1"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_2"
                :label="$t('trans.address_street_name')"
                v-model="next_of_kin_address_2"
                :placeholder="$t('trans.ui_eg_street')"
                :disabled="sameAsContactAddress"
                @input="clearFormErrors(form, 'next_of_kin_address_2')"
                :error="form.errors.next_of_kin_address_2"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_3"
                :label="$t('trans.address_suburb')"
                v-model="next_of_kin_address_3"
                :placeholder="$t('trans.ui_eg_suburb')"
                :disabled="sameAsContactAddress"
                @input="clearFormErrors(form, 'next_of_kin_address_3')"
                :error="form.errors.next_of_kin_address_3"
                :is-required="true"
            />
            <BaseInput
                input-id="next_of_kin_address_4"
                :label="$t('trans.address_city_town')"
                v-model="next_of_kin_address_4"
                :placeholder="$t('trans.ui_eg_city_town')"
                :disabled="sameAsContactAddress"
            />
        </div>
    </component>
</template>
