<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

interface Props {
    user: User;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'personal_details' }];
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col w-full">
            <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col sm:flex-row items-center gap-6">
                <!-- Profile Picture -->
                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-indigo-500">
                    <img
                        src="https://i.pravatar.cc/300?img=13"
                        alt="Student Profile"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Student Info -->
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-800">James Gudhlanga</h2>
                    <p class="text-gray-500">BSc Computer Science</p>
                    <p class="mt-2 text-sm text-gray-600">
                        📍 University of Cape Town
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <span class="font-semibold">Student ID:</span> 20231234
                        </div>
                        <div>
                            <span class="font-semibold">Email:</span> james.g@uct.ac.za
                        </div>
                        <div>
                            <span class="font-semibold">Phone:</span> +27 71 234 5678
                        </div>
                        <div>
                            <span class="font-semibold">Enrolled:</span> 2023 - Present
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-2">Courses</h3>
                    <ul class="list-disc list-inside text-gray-700 text-sm">
                        <li>Web Development</li>
                        <li>Data Structures</li>
                        <li>Artificial Intelligence</li>
                        <li>Database Systems</li>
                    </ul>
                </div>

                <div class="bg-white shadow rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-2">Skills</h3>
                    <ul class="list-disc list-inside text-gray-700 text-sm">
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
