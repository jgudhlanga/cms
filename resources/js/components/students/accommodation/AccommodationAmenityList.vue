<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { amenityIconName } from '@/lib/hms/roomSectionDisplay';
import type { HostelAllocationAmenity } from '@/types/hms';
import { computed } from 'vue';

interface Props {
    amenities: HostelAllocationAmenity[];
}

const props = defineProps<Props>();

const { formatCurrency } = useUtils();

const hasAmenityMarketValues = computed(
    () => props.amenities.some((amenity) => amenity.marketValue != null),
);

function amenityMarketValueLabel(amenity: HostelAllocationAmenity): string | null {
    if (amenity.marketValue == null) {
        return null;
    }

    return `USD ${formatCurrency(String(amenity.marketValue))}`;
}
</script>

<template>
    <div>
        <div class="mb-2">
            <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_amenities') }}</p>
            <p
                v-if="hasAmenityMarketValues"
                class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
            >
                {{ $t('students.accommodation_amenity_market_value_hint') }}
            </p>
        </div>
        <div
            v-if="amenities.length"
            class="flex flex-wrap gap-1.5"
        >
            <span
                v-for="amenity in amenities"
                :key="amenity.id"
                class="inline-flex max-w-full items-center gap-1 whitespace-nowrap rounded-lg border border-primary/15 bg-primary/5 px-2 py-0.5 text-[11px] font-medium text-foreground"
            >
                <BaseIcon
                    :name="amenityIconName(amenity.slug ?? amenity.name)"
                    class="h-3 w-3 shrink-0 text-primary"
                />
                <span class="max-w-32 truncate">{{ amenity.name }}</span>
                <template v-if="amenityMarketValueLabel(amenity)">
                    <span class="text-muted-foreground">·</span>
                    <span class="shrink-0 font-normal text-muted-foreground">
                        {{ amenityMarketValueLabel(amenity) }}
                    </span>
                </template>
            </span>
        </div>
        <p v-else class="text-sm text-muted-foreground">{{ $t('students.accommodation_no_amenities') }}</p>
    </div>
</template>
