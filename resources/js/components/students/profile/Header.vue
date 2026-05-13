<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3';
import { PageProps } from '@/types';
import { useInitials } from '@/composables/core/useInitials';
import { useDefaults } from '@/composables/core/useDefaults';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
const page = usePage<PageProps>();
const { user } = page.props.auth;
const student = ref({
          fullName: "Rutendo Chikwanda",
          studentId: "B20/ICT/001234/22",
          program: "National Diploma in ICT",
          status: "Active",
          year: "Year 2",
          semester: "Sem 3",
          attendanceMode: "Full-time",
          department: "ICT Dept.",
          academicYear: "2025/2026",
        });
        const yearSemesterDisplay = computed(() => {
          return `${student.value.year} · ${student.value.semester}`;
        });
        const { getInitials } = useInitials();
        const { defaultAvatarImage } = useDefaults();
</script>
<template> 
  <div class="w-full mx-auto">
          <div class="bg-white">
            <div class="py-3">
              <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-5">
                <div class="flex gap-4 flex-wrap">
                  <div class="flex-shrink-0">
                    <Avatar class="rounded-lg size-14">
                          <AvatarImage
                              :src="user.attributes.avatarUrl ?? defaultAvatarImage"
                              :alt="user.attributes.name"
                          />
                          <AvatarFallback class="rounded-lg size-8">
                              {{ getInitials(user.attributes.name) }}
                          </AvatarFallback>
                      </Avatar>
                  </div>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h1 class="text-2xl  font-extrabold tracking-tight text-slate-800 uppercase">{{ user.attributes.name }}</h1>
                      <span class="inline-flex md:hidden items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                        {{ student.status }}
                      </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 text-gray-600 text-sm sm:text-base">
                      <span class="font-mono tracking-wide bg-gray-50 px-1.5 py-0.5 rounded-md">{{ student.studentId }}</span>
                      <span class="text-gray-400 text-lg leading-3">·</span>
                      <span class="font-medium text-gray-700">{{ student.program }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                      <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 border border-emerald-200 shadow-sm info-chip">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        {{ student.status }}
                      </span>
                      <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-800 border border-indigo-100 shadow-sm">
                        📅 {{ yearSemesterDisplay }}
                      </span>
                      <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-800 border border-sky-100 shadow-sm">
                        ⏱️ {{ student.attendanceMode }}
                      </span>
                      <!-- Department badge -->
                      <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-800 border border-purple-100 shadow-sm">
                        🏛️ {{ student.department }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex-shrink-0 w-full md:w-auto">
                  <button 
                    class="group relative w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transform active:scale-95"
                  >
                    <svg class="w-4 h-4 transition-transform group-hover:translate-y-[-1px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Export</span>
                    <span class="absolute inset-0 rounded-xl bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></span>
                  </button>
                  <p class="text-[11px] text-gray-400 text-center md:text-right mt-1.5">Download profile data</p>
                </div>
              </div>
            </div>
          </div>
        </div>
</template>
<style scoped>
    .card-hover {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
    }
    /* custom chip styling for consistent badges */
    .info-chip {
      transition: all 0.15s ease;
    }
    button:active {
      transform: scale(0.97);
    }
  </style>