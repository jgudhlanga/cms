<script setup lang="ts">
import IdPhotoPanel from '@/pages/students/components/profile/IdPhotoPanel.vue';
import OfferLettersPanel from '@/components/students/profile/OfferLettersPanel.vue';
import type { Student } from '@/types/students';

interface Props {
    student: Student;
    offerLetterIntakePeriodIds?: Array<string | number>;
}

withDefaults(defineProps<Props>(), {
    offerLetterIntakePeriodIds: () => [],
});
</script>

<template>
    <div class="space-y-4">
        <IdPhotoPanel :student="student" />
        <OfferLettersPanel
            :student="student"
            :offer-letter-intake-period-ids="offerLetterIntakePeriodIds"
        />
    </div>
</template>
