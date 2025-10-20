<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';

defineProps<{ classSize: string | number }>();
</script>

<template>
    <div class="flex flex-col">
        <BaseAlert
            :type="TypeVariant.info"
            description="Each student's score is derived from their exam results using a weighted formula where lower scores indicate better performance."
        >
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                <div class="flex">
                    <span class="text-accent-foreground font-semibold">Scoring Formula:</span>
                    <span>• Single Sitting → <b>A=1</b>, <b>B=2</b>, <b>C=3</b></span>
                    <span>• Multiple Sittings →</span>
                    <span> <b>Year₁:</b> A=1, B=2, C=3; <b>Year₂:</b> A=2, B=3, C=4; <b>Year₃:</b> A=3, B=4, C=5 </span>(e.t.c)
                </div>
                <div class="flex space-x-3 rounded-full bg-persian-200 px-3 py-1 text-sm font-medium text-persian-800">
                    <div>Class size:</div>
                    <div class="font-bold">{{ classSize }}</div>
                </div>
            </div>
        </BaseAlert>
    </div>
</template>
