<script lang="ts" setup>
import Heading from '@/components/core/util/Heading.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Separator } from '@/components/ui/separator';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

defineProps<Props>();
const csDepartmentData = {
    departmentName: 'Computer Science',
    welcomeMessage: 'Empowering the next generation of tech innovators through research, coding excellence, and community-driven innovation.',
    headOfDepartment: 'Dr. James Gudhlanga',
    collegeName: 'Greenfield College',
    fieldOfStudy: 'computer programming, data science, artificial intelligence, and cybersecurity',
    location: 'Room 101, Science & Technology Building, Main Campus',
    email: 'csdept@greenfield.edu',
    phone: '+27 12 345 6789',
    website: 'https://cs.greenfield.edu',
    bannerImage: '/images/departments/computer-science-banner.jpg',
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
                <Heading :title="`Welcome to the Department of ${department.attributes?.department}`" />
                <blockquote class="mt-2 border-l-2 pl-6 italic text-muted-foreground">
                    {{ `" ${csDepartmentData.welcomeMessage}"` }}
                </blockquote>
                <Separator class="mt-2" />
                <div class="text-muted-foreground text-xs font-extralight">— {{ csDepartmentData.headOfDepartment }}</div>
            </div>
            <!-- About Us -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.1 } }">
                <HeadingSmall title="About Us" />
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        `The Department of ${department.attributes?.department} at ${csDepartmentData.collegeName} is committed to academic excellence,
                    research innovation, and empowering students with real-world skills in the field of ${csDepartmentData.fieldOfStudy}`
                    }}
                </p>
            </div>
            <!-- What We Offer -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.2 } }">
                <HeadingSmall title="What we offer" />
                <ul class="text-sm text-muted-foreground list-inside list-disc">
                    <li>Undergraduate & Postgraduate Programs</li>
                    <li>World-Class Faculty & Mentorship</li>
                    <li>Hands-On Learning & Industry Projects</li>
                    <li>Career Support & Alumni Network</li>
                </ul>
            </div>
            <!-- Facilities -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.3 } }">
                <HeadingSmall title="Facilities & Resources" />
                <ul class="text-sm text-muted-foreground list-inside list-disc">
                    <li>Modern lecture halls and labs</li>
                    <li>Department library and digital resources</li>
                    <li>Innovation hubs and collaborative spaces</li>
                </ul>
            </div>
            <!-- Contact -->
            <div v-motion :initial="{ opacity: 0, y: 40 }" :enter="{ opacity: 1, y: 0, transition: { delay: 0.5 } }">
                <HeadingSmall title="Contact us" />
                <p class="text-gray-700">
                    <strong>Location:</strong> {{ csDepartmentData.location }}<br />
                    <strong>Email:</strong>
                    <a :href="'mailto:' + csDepartmentData.email" class="text-blue-600 hover:underline">{{ csDepartmentData.email }}</a
                    ><br />
                    <strong>Phone:</strong> {{ csDepartmentData.phone }}<br />
                    <strong>Website:</strong>
                    <a :href="csDepartmentData.website" target="_blank" class="text-blue-600 hover:underline">{{ csDepartmentData.website }}</a>
                </p>
            </div>
        </div>
    </section>
</template>
