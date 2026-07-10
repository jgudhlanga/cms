<script setup lang="ts">
import type { HostelApplicationAllocationPreview } from '@/types/hms';

interface Props {
    preview: HostelApplicationAllocationPreview;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-border bg-muted/20 p-4">
        <p class="text-sm font-semibold text-foreground">
            {{ $t('hms.allocation_preview_title') }}
        </p>
        <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-muted-foreground">{{ $t('hms.allocation_preview_hostel') }}</dt>
                <dd class="font-medium text-foreground">{{ preview.hostelName }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">{{ $t('hms.allocation_preview_room') }}</dt>
                <dd class="font-medium text-foreground">{{ preview.roomName }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">{{ $t('hms.allocation_preview_section') }}</dt>
                <dd class="font-medium text-foreground">{{ preview.sectionName }}</dd>
            </div>
        </dl>
    </div>
</template>
