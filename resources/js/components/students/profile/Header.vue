<script setup lang="ts">
import { computed } from 'vue'
import { useInitials } from '@/composables/core/useInitials';
import { useDefaults } from '@/composables/core/useDefaults';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { StudentHeader } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { IconName, icons } from '@/lib/icons';


  interface Props {
    data: StudentHeader | null;
  }
  const props = defineProps<Props>();
    
    const yearSemesterDisplay = computed(() => {
      return `${props.data?.academicCalendar} · ${props.data?.academicYearOption}`;
    });
    const levelCourseDisplay = computed(() => {
      return `${props.data?.level} ${trans('general.in')} ${props.data?.course}`;
    });
    const { getInitials } = useInitials();
    const { defaultAvatarImage } = useDefaults();

   const avatarImage = () => {
    return props.data?.avatarUrl?.trim()
      ? props.data.avatarUrl
      : defaultAvatarImage;
   };

</script>
<template> 
  <div class="w-full mx-auto">
          <div class="bg-white">
            <div class="py-3">
              <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-5">
                <div class="flex gap-4 flex-wrap">
                  <div class="flex-shrink-0">
                    <Avatar class="rounded-full size-20">
                          <AvatarImage
                              :src="avatarImage()"
                              :alt="data?.studentName"
                          />
                          <AvatarFallback class="rounded-full size-8">
                              {{ getInitials(data?.studentName) }}
                          </AvatarFallback>
                      </Avatar>
                  </div>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h1 class="text-2xl  font-extrabold tracking-tight text-slate-800 uppercase">{{ data?.studentName }}</h1>
                      <span class="inline-flex  items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                        {{ data?.enrolmentStatus }}  
                      </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 text-gray-600 text-sm sm:text-base">
                      <span class="font-mono tracking-wide bg-gray-100 shadow-sm px-1.5 py-0.5 rounded-full">{{ data?.studentNumber }}</span>
                      <span class="font-medium text-gray-700 uppercase">{{ levelCourseDisplay }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                      <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-800 border border-indigo-100 shadow-sm">
                        📅 {{ yearSemesterDisplay }}
                      </span>
                      <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-800 border border-sky-100 shadow-sm">
                        ⏱️ {{ data?.modeOfStudy }}
                      </span>
                      <!-- Department badge -->
                      <span class="inline-flex items-center rounded-full bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-800 border border-purple-100 shadow-sm">
                        🏛️ {{ data?.department }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex-shrink-0 w-full md:w-auto">
                  <button 
                    class="group relative w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transform active:scale-95"
                  >
                    <component :is="icons[IconName.download]" class="w-4 h-4 transition-transform group-hover:translate-y-[-1px]" />
                    <span>{{ $t('general.export') }}</span>
                    <span class="absolute inset-0 rounded-xl bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></span>
                  </button>
                  <p class="text-[11px] text-gray-400 text-center md:text-right mt-1.5">{{ $t('general.download_profile_data') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
</template>
<style scoped>
    button:active {
      transform: scale(0.97);
    }
  </style>