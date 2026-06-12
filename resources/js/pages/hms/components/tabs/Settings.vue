<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { TypeVariant } from '@/enums/type-variants';
import { useHms } from '@/composables/hms/useHms';
import { hasAbility } from '@/lib/permissions';
import type { HmsSettings } from '@/types/hms';
import { onMounted, ref } from 'vue';

const { fetchHmsSettings, saveHmsSettings, isLoading } = useHms();

const settings = ref<HmsSettings | null>(null);
const form = ref({
    applicationsOpen: false,
    applicationStartDate: null as string | null,
    applicationEndDate: null as string | null,
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
        applicationsOpen: res.attributes.applicationsOpen ?? false,
        applicationStartDate: res.attributes.applicationStartDate ?? null,
        applicationEndDate: res.attributes.applicationEndDate ?? null,
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

        <form v-else class="space-y-6" @submit.prevent="save">
            <div class="space-y-4 rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">{{ $t('hms.settings_application_window_description') }}</p>

                <BaseSwitch
                    input-id="applications_open"
                    v-model="form.applicationsOpen"
                    :label="$t('hms.applications_open')"
                    :on-update="(value) => (form.applicationsOpen = value)"
                />

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <BaseDatePicker
                        input-id="application_start_date"
                        v-model="form.applicationStartDate"
                        :label="$t('hms.application_start_date')"
                        :enable-time-picker="false"
                        :teleport="true"
                        :disabled="!form.applicationsOpen"
                    />
                    <BaseDatePicker
                        input-id="application_end_date"
                        v-model="form.applicationEndDate"
                        :label="$t('hms.application_end_date')"
                        :enable-time-picker="false"
                        :teleport="true"
                        :disabled="!form.applicationsOpen"
                    />
                </div>
            </div>

            <div class="space-y-4">
                <BaseSwitch
                    input-id="require_full_time"
                    v-model="form.requireFullTimeStudy"
                    :label="$t('hms.require_full_time_study')"
                    :on-update="(value) => (form.requireFullTimeStudy = value)"
                />
                <BaseInput
                    input-id="full_time_mode_name"
                    v-model="form.fullTimeModeName"
                    :label="$t('hms.full_time_mode_name')"
                    :disabled="!form.requireFullTimeStudy"
                />
                <BaseSwitch
                    input-id="require_tuition"
                    v-model="form.requireTuitionPaid"
                    :label="$t('hms.require_tuition_paid')"
                    :on-update="(value) => (form.requireTuitionPaid = value)"
                />
                <BaseSwitch
                    input-id="require_accommodation"
                    v-model="form.requireAccommodationPaid"
                    :label="$t('hms.require_accommodation_paid')"
                    :on-update="(value) => (form.requireAccommodationPaid = value)"
                />
                <BaseSwitch
                    input-id="require_address"
                    v-model="form.requireAddressOutsideCampus"
                    :label="$t('hms.require_address_outside_campus')"
                    :on-update="(value) => (form.requireAddressOutsideCampus = value)"
                />
                <BaseInput
                    input-id="campus_city"
                    v-model="form.campusCity"
                    :label="$t('hms.campus_city')"
                    :disabled="!form.requireAddressOutsideCampus"
                />
                <BaseSwitch
                    input-id="allow_guests"
                    v-model="form.allowGuests"
                    :label="$t('hms.allow_guests')"
                    :on-update="(value) => (form.allowGuests = value)"
                />
            </div>

            <div class="flex">
                <BaseButton type="submit" :disabled="isLoading" :title="$t('trans.save')" />
            </div>
        </form>
    </div>
</template>
