<script lang="ts" setup>
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { Separator } from '@/components/ui/separator';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

defineProps<Props>();
const csDepartmentData = {
    welcomeMessage: 'Empowering the next generation of tech innovators through research, coding excellence, and community-driven innovation.',
    headOfDepartment: 'Dr James Gudhlanga',
    collegeName: 'Harare Polytechnic College',
    fieldOfStudy: 'computer programming, data science, artificial intelligence, and cybersecurity',
    location: 'Room 101, Science & Technology Building, Main Campus',
    email: 'department@hreploy.test',
    phone: '+263 12 345 6789',
};
</script>
<template>
    <section class="flex flex-col pt-4">
        <div class="flex flex-col space-y-12">
            <!-- Header Section -->
            <div
                class="flex flex-col space-y-2"
                v-motion
                :initial="{ opacity: 0, y: 40 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 0.5 } }"
            >
                <HeadingSmall :title="`Welcome to the Department of ${department.attributes?.department}`" />
                <blockquote class="border-primary mt-2 flex w-full flex-col border-x-4 pl-6 italic">
                    {{ `" ${csDepartmentData.welcomeMessage}"` }}
                </blockquote>
                <Separator class="mt-2" />
                <div class="text-xs font-stretch-extra-condensed">{{ `--${csDepartmentData.headOfDepartment} (HOD)` }}</div>
            </div>
            <!-- About Us -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.1 } }">
                <HeadingSmall :title="$t('trans.ui_about_us')" />
                <div class="mt-3 flex w-full flex-col">
                    {{
                        `The Department of ${department.attributes?.department} at ${csDepartmentData.collegeName} is committed to academic excellence,
                    research innovation, and empowering students with real-world skills in the field of ${csDepartmentData.fieldOfStudy}`
                    }}
                </div>
            </div>
            <!-- What We Offer -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.2 } }">
                <HeadingSmall :title="$t('trans.ui_what_we_offer')" />
                <ul class="mt-3 list-inside list-disc">
                    <li>{{ $t('trans.ui_undergraduate_and_postgraduate_programs') }}</li>
                    <li>{{ $t('trans.ui_world_class_faculty_and_mentorship') }}</li>
                    <li>{{ $t('trans.ui_hands_on_learning_and_industry_projects') }}</li>
                    <li>{{ $t('trans.ui_career_support_and_alumni_network') }}</li>
                </ul>
            </div>
            <!-- Facilities -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.3 } }">
                <HeadingSmall :title="$t('trans.ui_facilities_and_resources')" />
                <ul class="mt-3 list-inside list-disc">
                    <li>{{ $t('trans.ui_modern_lecture_halls_and_labs') }}</li>
                    <li>{{ $t('trans.ui_department_library_and_digital_resources') }}</li>
                    <li>{{ $t('trans.ui_innovation_hubs_and_collaborative_spaces') }}</li>
                </ul>
            </div>
            <!-- Contact -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.5 } }">
                <HeadingSmall :title="$t('trans.ui_contact_us')" />
                <div class="mt-3 flex w-full flex-col md:w-1/3">
                    <LabelValue :label="'Location'" :value="csDepartmentData.location" />
                    <LabelValue :label="'Email'" :value="csDepartmentData.email" />
                    <LabelValue :label="'Phone'" :value="csDepartmentData.phone" />
                </div>
            </div>
        </div>
    </section>
</template>
