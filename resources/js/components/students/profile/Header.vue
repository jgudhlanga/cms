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
  <section class="px-2 py-1">
              <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div class="flex flex-wrap gap-4">
                  <div class="shrink-0">
                    <Avatar class="size-20 rounded-full">
                          <AvatarImage
                              :src="avatarImage()"
                              :alt="data?.studentName"
                          />
                          <AvatarFallback class="size-8 rounded-full">
                              {{ getInitials(data?.studentName) }}
                          </AvatarFallback>
                      </Avatar>
                  </div>
                  <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                      <h1 class="text-2xl font-extrabold uppercase tracking-tight text-foreground">{{ data?.studentName }}</h1>
                      <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-400">
                        {{ data?.enrolmentStatus }}  
                      </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground sm:text-base">
                      <span class="rounded-full bg-muted px-1.5 py-0.5 font-mono tracking-wide text-foreground shadow-sm">{{ data?.studentNumber }}</span>
                      <span class="font-medium uppercase text-foreground">{{ levelCourseDisplay }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                      <span class="inline-flex items-center rounded-full border border-primary/30 bg-primary/15 px-3 py-1.5 text-xs font-medium text-primary shadow-sm">
                        📅 {{ yearSemesterDisplay }}
                      </span>
                      <span class="inline-flex items-center rounded-full border border-sky-500/30 bg-sky-500/15 px-3 py-1.5 text-xs font-medium text-sky-400 shadow-sm">
                        ⏱️ {{ data?.modeOfStudy }}
                      </span>
                      <span class="inline-flex items-center rounded-full border border-purple-500/30 bg-purple-500/15 px-3 py-1.5 text-xs font-medium text-purple-400 shadow-sm">
                        🏛️ {{ data?.department }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="w-full shrink-0 md:w-auto">
                  <button 
                    class="group relative inline-flex w-full transform items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 font-semibold text-primary-foreground shadow-md transition-all duration-200 ease-out hover:bg-primary/90 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background active:scale-95 md:w-auto"
                  >
                    <component :is="icons[IconName.download]" class="h-4 w-4 transition-transform group-hover:translate-y-[-1px]" />
                    <span>{{ $t('general.export') }}</span>
                    <span class="pointer-events-none absolute inset-0 rounded-xl bg-primary-foreground/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                  </button>
                  <p class="mt-1.5 text-center text-[11px] text-muted-foreground md:text-right">{{ $t('general.download_profile_data') }}</p>
                </div>
              </div>
  </section>
</template>
<style scoped>
    button:active {
      transform: scale(0.97);
    }
  </style>