<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseCheckbox from '@/components/core/form/radio/BaseCheckbox.vue';
import { TypeVariant } from '@/enums/type-variants';
import { useHms } from '@/composables/hms/useHms';
import { hasAbility } from '@/lib/permissions';
import type { HmsSettings } from '@/types/hms';
import { onMounted, ref } from 'vue';

const { fetchHmsSettings, saveHmsSettings, isLoading } = useHms();

const settings = ref<HmsSettings | null>(null);
const form = ref({
    requireFullTimeStudy: true,
    fullTimeModeName: '',
    requireTuitionPaid: true,
    requireAccommodationPaid: true,
    requireAddressOutsideCampus: true,
    campusCity: '',
    allowGuests: false,
});

const loadSettings = async () => {
    const res = await fetchHmsSettings();
    if (!res) return;
    settings.value = res;
    form.value = {
        requireFullTimeStudy: res.attributes.requireFullTimeStudy,
        fullTimeModeName: res.attributes.fullTimeModeName,
        requireTuitionPaid: res.attributes.requireTuitionPaid,
        requireAccommodationPaid: res.attributes.requireAccommodationPaid,
        requireAddressOutsideCampus: res.attributes.requireAddressOutsideCampus,
        campusCity: res.attributes.campusCity,
        allowGuests: res.attributes.allowGuests ?? false,
    };
};

const save = async () => {
    if (!settings.value) return;
    const ok = await saveHmsSettings(settings.value, { ...form.value });
    if (ok) await loadSettings();
};

onMounted(() => loadSettings());
</script>

<template>
    <div class="max-w-2xl space-y-6">
        <p class="text-sm text-muted-foreground">{{ $t('hms.settings_description') }}</p>

        <BaseAlert
            v-if="!hasAbility('update:hms-settings')"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
            :type="TypeVariant.danger"
        />

        <form v-else class="space-y-4 space-x-4" @submit.prevent="save">
            <BaseCheckbox
                input-id="require_full_time"
                v-model="form.requireFullTimeStudy"
                :label="$t('hms.require_full_time_study')"
            />
            <BaseInput
                input-id="full_time_mode_name"
                v-model="form.fullTimeModeName"
                :label="$t('hms.full_time_mode_name')"
                :disabled="!form.requireFullTimeStudy"
            />
            <BaseCheckbox
                input-id="require_tuition"
                v-model="form.requireTuitionPaid"
                :label="$t('hms.require_tuition_paid')"
            />
            <BaseCheckbox
                input-id="require_accommodation"
                v-model="form.requireAccommodationPaid"
                :label="$t('hms.require_accommodation_paid')"
            />
            <BaseCheckbox
                input-id="require_address"
                v-model="form.requireAddressOutsideCampus"
                :label="$t('hms.require_address_outside_campus')"
            />
            <BaseInput
                input-id="campus_city"
                v-model="form.campusCity"
                :label="$t('hms.campus_city')"
                :disabled="!form.requireAddressOutsideCampus"
            />
            <BaseCheckbox
                input-id="allow_guests"
                v-model="form.allowGuests"
                :label="$t('hms.allow_guests')"
            />

            <div class="flex">
                <BaseButton type="submit" :disabled="isLoading" :title="$t('trans.save')" />
            </div>
        </form>
    </div>
</template>
