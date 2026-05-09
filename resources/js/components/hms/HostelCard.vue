<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { computed } from 'vue';

type HostelWarden = {
    id: number | string;
    user?: { full_name?: string | null } | null;
};

type Hostel = {
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    warden?: HostelWarden | null;
};

interface Props {
    hostel: Hostel;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'edit', hostel: Hostel): void;
    (e: 'delete', hostel: Hostel): void;
}>();

const statusVariant = computed(() => (props.hostel.status === 'active' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'));
const typeLabel = computed(() => {
    if (!props.hostel.type) return null;
    const map: Record<string, string> = { male: 'Boys', female: 'Girls', mixed: 'Mixed' };
    return map[props.hostel.type] ?? props.hostel.type;
});
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
        <div class="border-b border-gray-100 px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <BaseIcon :name="IconName.company" />
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-foreground">
                                {{ hostel.name }}
                            </h3>
                            <span v-if="typeLabel" class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                {{ typeLabel }}
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span v-if="hostel.location" class="flex items-center gap-1">
                                <BaseIcon :name="IconName.location" class="h-3 w-3" />
                                {{ hostel.location }}
                            </span>
                            <span class="flex items-center gap-1">
                                <BaseIcon :name="IconName.users" class="h-3 w-3" />
                                {{ 0 }}/{{ hostel.capacity }}
                            </span>
                        </div>
                    </div>
                </div>

                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold" :class="statusVariant">
                    {{ hostel.status === 'active' ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div class="px-4 py-3">
            <div class="grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-md bg-muted/40 p-3">
                    <div class="text-xs text-muted-foreground">Floors</div>
                    <div class="mt-1 font-semibold">{{ hostel.floor_count }}</div>
                </div>
                <div class="rounded-md bg-muted/40 p-3">
                    <div class="text-xs text-muted-foreground">Rooms</div>
                    <div class="mt-1 font-semibold">{{ hostel.rooms_count }}</div>
                </div>
                <div class="rounded-md bg-muted/40 p-3">
                    <div class="text-xs text-muted-foreground">Capacity</div>
                    <div class="mt-1 font-semibold">{{ hostel.capacity }}</div>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                <div class="text-muted-foreground">
                    <span class="font-medium text-foreground">Warden:</span>
                    <span class="ml-1">{{ hostel.warden?.user?.full_name ?? 'Unassigned' }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <IconButton :icon="IconName.edit" :variant="ColorVariant.success_outline" @click="emit('edit', hostel)" />
                    <IconButton :icon="IconName.trash" :variant="ColorVariant.danger_outline" @click="emit('delete', hostel)" />
                </div>
            </div>
        </div>
    </div>
</template>

