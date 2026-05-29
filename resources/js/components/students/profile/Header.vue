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
    <div class="flex flex-col">
      <div class="grid grid-cols-[5rem_1fr] gap-x-4 gap-y-2">
        <div class="row-span-2 self-stretch">
          <Avatar class="h-full w-full rounded-full">
            <AvatarImage
              :src="avatarImage()"
              :alt="data?.studentName"
            />
            <AvatarFallback class="h-full w-full rounded-full">
              {{ getInitials(data?.studentName) }}
            </AvatarFallback>
          </Avatar>
        </div>
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
      </div>
      <div class="flex flex-wrap items-center gap-2">
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
  </section>
</template>
<style scoped>
    button:active {
      transform: scale(0.97);
    }
  </style>