<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import InputError from '@/components/core/form/InputError.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import ApplicationGuestSection from '@/pages/hms/components/forms/application/ApplicationGuestSection.vue';
import ApplicationStudentSection from '@/pages/hms/components/forms/application/ApplicationStudentSection.vue';
import ApplicationTypeSelector from '@/pages/hms/components/forms/application/ApplicationTypeSelector.vue';
import { useHostelApplicationForm } from '@/composables/hms/useHostelApplicationForm';
import { TypeVariant } from '@/enums/type-variants';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';

const {
    form,
    applicationType,
    applicationTypeOptions,
    isStudent,
    isGuest,
    showStudentLookupGrid,
    showStudentSearchHelper,
    showSaveButton,
    studentSearch,
    isSearching,
    lookup,
    eligibility,
    eligibilityPassed,
    canSubmitFromLookup,
    lookupBlockers,
    semesterInfo,
    roomAvailabilityInfo,
    gender,
    saveValidationError,
    searchStudent,
    save,
} = useHostelApplicationForm();
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_applications"
        :title="$t('hms.create_application')"
        :size="SizeVariant.xl"
        :action-btn-text="'trans.save'"
        :show-action-button="showSaveButton"
        :on-form-action="() => save()"
        :form="form"
    >
        <template #body>
            <div class="space-y-6">
                <ApplicationTypeSelector
                    v-model="applicationType"
                    :options="applicationTypeOptions"
                />

                <BaseAlert
                    :description="$t('hms.room_assignment_notice')"
                    :type="TypeVariant.info"
                />

                <ApplicationStudentSection
                    v-if="isStudent"
                    v-model="studentSearch"
                    :is-searching="isSearching"
                    :lookup="lookup"
                    :show-student-lookup-grid="showStudentLookupGrid"
                    :show-student-search-helper="showStudentSearchHelper"
                    :check-in="form.checkIn"
                    :check-out="form.checkOut"
                    :semester-info="semesterInfo"
                    :room-availability-info="roomAvailabilityInfo"
                    :can-submit-from-lookup="canSubmitFromLookup"
                    :lookup-blockers="lookupBlockers"
                    :eligibility="eligibility"
                    :eligibility-passed="eligibilityPassed"
                    @search="searchStudent"
                />

                <ApplicationGuestSection
                    v-if="isGuest"
                    v-model:gender="gender"
                    :form="form"
                />

                <BaseAlert
                    v-if="saveValidationError"
                    :description="saveValidationError"
                    :type="TypeVariant.danger"
                />

                <InputError v-for="(error, key) in form.errors" :key="key" :message="error" />
            </div>
        </template>
    </BaseModal>
</template>
