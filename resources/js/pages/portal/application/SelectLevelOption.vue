<script setup lang="ts">
// UI components
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { AuthObject } from '@/types/data-pagination';
import { Level } from '@/types/institution';

// Utilities

// Props
interface Props {
    levels: Level[];
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const { selectLevel } = useStudentPortal();
</script>
<template>
    <StudentPageHeader />
    <div class="mt-20 flex w-full flex-col items-center justify-center bg-white px-5">
        <!-- Cards Grid -->
        <div class="mb-10 grid grid-cols-1 gap-6 md:grid-cols-4">
            <!-- Card 1 -->
            <div
                v-for="(level, index) in levels"
                :key="level.id"
                class="card-hover fade-in overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl"
                :style="{ 'animation-delay': Number(level.id) * 0.1 + 's' }"
            >
                <!-- Card Header -->
                <div class="relative">
                    <div class="absolute top-4 right-4 z-10">
                        <span class="rounded-full bg-white/90 px-3 py-1 text-sm font-bold text-gray-800 shadow backdrop-blur-sm">
                            #{{ Number(index) + 1 }}
                        </span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">{{ level.attributes.name }}</h3>
                                <p class="mt-1 text-xs text-gray-500">{{ level.attributes.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-center">
                        <button
                            @click="() => selectLevel(String(level.id))"
                            class="apply-button"
                            :disabled="!level.attributes.showOnCurrentApplicationPeriod"
                            :class="!level.attributes.showOnCurrentApplicationPeriod ? 'cursor-not-allowed opacity-50' : ''"
                        >
                            {{ $t('general.apply_now') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.apply-button {
    display: inline-flex;
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #2342f5 0%, #00d2ff 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 600;
    justify-content: center;
    align-items: center;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
</style>
