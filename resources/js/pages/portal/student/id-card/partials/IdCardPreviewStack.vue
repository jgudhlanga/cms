<script setup lang="ts">
import IdCardPreview from '@/pages/portal/student/id-card/partials/IdCardPreview.vue';
import IdCardPreviewBack from '@/pages/portal/student/id-card/partials/IdCardPreviewBack.vue';

interface Props {
    studentName: string;
    studentNumber: string;
    department?: string | null;
    level?: string | null;
    course?: string | null;
    mode?: string | null;
    sdp?: string | null;
    residence?: string | null;
    expiryDate?: string | null;
    photoUrl?: string | null;
    serialNumber?: string | null;
    nationalId?: string | null;
    identityLabel?: string | null;
    returnName?: string | null;
    returnAddress?: string | null;
    returnPhone?: string | null;
    logoUrl?: string | null;
    institutionName?: string | null;
    website?: string | null;
    signatureUrl?: string | null;
}

withDefaults(defineProps<Props>(), {
    department: null,
    level: null,
    course: null,
    mode: null,
    sdp: null,
    residence: null,
    expiryDate: null,
    photoUrl: null,
    serialNumber: null,
    nationalId: null,
    identityLabel: null,
    returnName: null,
    returnAddress: null,
    returnPhone: null,
    logoUrl: null,
    institutionName: null,
    website: null,
    signatureUrl: null,
});
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-1.5">
            <p class="text-center text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                {{ $t('trans.student_id_card_front') }}
            </p>
            <IdCardPreview
                :student-name="studentName"
                :student-number="studentNumber"
                :department="department"
                :level="level"
                :course="course"
                :mode="mode"
                :sdp="sdp"
                :residence="residence"
                :expiry-date="expiryDate"
                :photo-url="photoUrl"
                :logo-url="logoUrl"
                :institution-name="institutionName"
                :website="website"
            />
        </div>
        <div class="space-y-1.5">
            <p class="text-center text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                {{ $t('trans.student_id_card_back') }}
            </p>
            <IdCardPreviewBack
                :student-number="studentNumber"
                :serial-number="serialNumber"
                :national-id="nationalId"
                :identity-label="identityLabel"
                :return-name="returnName"
                :return-address="returnAddress"
                :return-phone="returnPhone"
                :logo-url="logoUrl"
                :institution-name="institutionName"
                :signature-url="signatureUrl"
            />
        </div>
    </div>
</template>
