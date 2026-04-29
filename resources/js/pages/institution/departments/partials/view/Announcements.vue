<script lang="ts" setup>
import Heading from '@/components/core/util/Heading.vue';
import { computed, ref } from 'vue';

const searchQuery = ref('');
const selectedCategory = ref('');

const notices = ref([
    {
        id: 1,
        title: 'Semester Exams Schedule Released',
        description: 'Check your departments for individual timetables.',
        date: '2025-07-12',
        category: 'Exams',
    },
    {
        id: 2,
        title: 'Library Reopening',
        description: 'The library will reopen from Monday, July 15.',
        date: '2025-07-10',
        category: 'General',
    },
    {
        id: 3,
        title: 'Sports Day Registration Open',
        description: 'Register by July 20 for the inter-college events.',
        date: '2025-07-08',
        category: 'Sports',
    },
    // Add more mock notices as needed
]);

const categories = computed(() => [...new Set(notices.value.map((n) => n.category))]);

const filteredNotices = computed(() => {
    return notices.value.filter((notice) => {
        const matchesSearch =
            notice.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            notice.description.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesCategory = !selectedCategory.value || notice.category === selectedCategory.value;
        return matchesSearch && matchesCategory;
    });
});
</script>
<template>
    <div class="flex flex-col py-6">
        <Heading :title="$t('trans.ui_department_notice_board')" :description="$t('trans.ui_stay_updated_with_the_latest_college_announcements')" />
        <!-- Search and Filter -->
        <section class="flex flex-col py-6">
            <div class="mb-6 flex flex-col items-center justify-between gap-4 md:flex-row">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('trans.ui_search_notices')"
                    class="w-full rounded-xl border border-gray-300 px-4 py-2 shadow-sm md:w-1/2"
                />
                <select v-model="selectedCategory" class="w-full rounded-xl border border-gray-300 px-4 py-2 shadow-sm md:w-1/4">
                    <option value="">{{ $t('trans.ui_all_categories') }}</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>
            </div>

            <!-- Notices Grid -->
            <transition-group name="list" tag="div" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="notice in filteredNotices" :key="notice.id" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-lg">
                    <h2 class="mb-2 text-xl font-semibold text-blue-700">
                        {{ notice.title }}
                    </h2>
                    <p class="mb-2 text-sm text-gray-600">{{ notice.category }}</p>
                    <p class="mb-4 text-gray-700">{{ notice.description }}</p>
                    <div class="text-right text-sm text-gray-500">
                        {{ notice.date }}
                    </div>
                </div>
            </transition-group>
        </section>
    </div>
</template>

<style scoped>
/* Fade animation for list */
.list-enter-active,
.list-leave-active {
    transition: all 0.4s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.list-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
