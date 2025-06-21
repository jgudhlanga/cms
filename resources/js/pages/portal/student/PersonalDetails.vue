<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import BaseImage from '@/components/core/image/BaseImage.vue';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'personal_details' }];
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <div class="flex flex-col items-start p-5 gap-6 rounded-2xl bg-white px-6 shadow-lg sm:flex-row">
                <!-- Profile Picture -->
                <div class="flex overflow-hidden">
                    <BaseImage
                        :src="''"
                        :is-person="true"
                        classes="w-[130px] h-[130px] rounded-full border-[1px] border-primary shadow-lg"
                    />
                </div>

                <!-- Student Info -->
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">{{ user.attributes?.name }}</h2>
                    <p class="text-gray-500">BSc Computer Science</p>
                    <p class="mt-2 text-sm text-gray-600">📍 University of Cape Town</p>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-700">
                        <div><span class="font-semibold">Student ID:</span> 20231234</div>
                        <div><span class="font-semibold">Email:</span> james.g@uct.ac.za</div>
                        <div><span class="font-semibold">Phone:</span> +27 71 234 5678</div>
                        <div><span class="font-semibold">Enrolled:</span> 2023 - Present</div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-2 text-lg font-semibold">Courses</h3>
                    <ul class="list-inside list-disc text-sm text-gray-700">
                        <li>Web Development</li>
                        <li>Data Structures</li>
                        <li>Artificial Intelligence</li>
                        <li>Database Systems</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="mb-2 text-lg font-semibold">Skills</h3>
                    <ul class="list-inside list-disc text-sm text-gray-700">
                        <li>JavaScript / Vue.js</li>
                        <li>Python</li>
                        <li>MySQL / PostgreSQL</li>
                        <li>Git / GitHub</li>
                    </ul>
                </div>
            </div>
        </div>
    </PageContainer>
</template>
