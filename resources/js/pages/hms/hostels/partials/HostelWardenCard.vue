<script setup lang="ts">
import type { HostelWardenProfile } from '@/types/hms';
import { Building2, Mail, MapPin, Phone, ShieldUser } from '@lucide/vue';
import { computed } from 'vue';

interface Props {
    wardenName: string;
    hostelName: string;
    wardenProfile?: HostelWardenProfile | null;
}

const props = defineProps<Props>();

const hasPersonalContact = computed(
    () =>
        Boolean(props.wardenProfile?.email?.trim()) || Boolean(props.wardenProfile?.phone?.trim()),
);

const departmentsWithContact = computed(() =>
    (props.wardenProfile?.departments ?? []).filter(
        (department) =>
            Boolean(department.email?.trim()) ||
            Boolean(department.phone?.trim()) ||
            Boolean(department.location?.trim()),
    ),
);

const hasAnyContact = computed(
    () => hasPersonalContact.value || departmentsWithContact.value.length > 0,
);
</script>

<template>
    <div class="rounded-2xl bg-linear-to-br from-indigo-950 to-indigo-600 p-6 text-white">
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full border-2 border-white/25 bg-white/15"
        >
            <ShieldUser class="h-7 w-7 text-white/90" />
        </div>
        <div class="font-serif text-xl font-bold">{{ wardenName }}</div>
        <div class="mt-1 text-xs text-white/50">
            {{ $t('hms.show_warden_role', { hostel: hostelName }) }}
        </div>

        <div class="my-4 h-px bg-white/10" />

        <div v-if="hasAnyContact" class="space-y-4 text-sm">
            <div v-if="hasPersonalContact">
                <div class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-white/40">
                    {{ $t('hms.show_warden_personal_contacts') }}
                </div>
                <ul class="space-y-2">
                    <li v-if="wardenProfile?.email" class="flex items-start gap-2">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-white/50" />
                        <a
                            :href="`mailto:${wardenProfile.email}`"
                            class="break-all text-white/90 underline-offset-2 hover:text-white hover:underline"
                        >
                            {{ wardenProfile.email }}
                        </a>
                    </li>
                    <li v-if="wardenProfile?.phone" class="flex items-start gap-2">
                        <Phone class="mt-0.5 h-4 w-4 shrink-0 text-white/50" />
                        <a
                            :href="`tel:${wardenProfile.phone}`"
                            class="text-white/90 underline-offset-2 hover:text-white hover:underline"
                        >
                            {{ wardenProfile.phone }}
                        </a>
                    </li>
                </ul>
            </div>

            <div v-if="departmentsWithContact.length">
                <div class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-white/40">
                    {{ $t('hms.show_warden_department_contacts') }}
                </div>
                <ul class="space-y-3">
                    <li
                        v-for="department in departmentsWithContact"
                        :key="department.id"
                        class="rounded-xl bg-white/10 px-3 py-2.5"
                    >
                        <div class="mb-1.5 flex items-center gap-2 font-semibold text-white">
                            <Building2 class="h-3.5 w-3.5 shrink-0 text-white/60" />
                            <span class="truncate">{{ department.name }}</span>
                            <span v-if="department.code" class="text-[10px] font-normal text-white/50">
                                ({{ department.code }})
                            </span>
                        </div>
                        <ul class="space-y-1.5 pl-5 text-xs text-white/80">
                            <li v-if="department.email" class="flex items-start gap-2">
                                <Mail class="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/45" />
                                <a
                                    :href="`mailto:${department.email}`"
                                    class="break-all underline-offset-2 hover:text-white hover:underline"
                                >
                                    {{ department.email }}
                                </a>
                            </li>
                            <li v-if="department.phone" class="flex items-start gap-2">
                                <Phone class="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/45" />
                                <a
                                    :href="`tel:${department.phone}`"
                                    class="underline-offset-2 hover:text-white hover:underline"
                                >
                                    {{ department.phone }}
                                </a>
                            </li>
                            <li v-if="department.location" class="flex items-start gap-2">
                                <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-white/45" />
                                <span>{{ department.location }}</span>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <p v-else class="text-sm text-white/60">
            {{ $t('hms.show_warden_contact_unavailable') }}
        </p>
    </div>
</template>
