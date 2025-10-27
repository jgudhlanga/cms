<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';

defineProps<{ classSize: string | number }>();
</script>

<template>
    <div class="flex flex-col">
        <BaseAlert
            :type="TypeVariant.info"
            description="Each student's score is derived from their exam results using a weighted formula where lower scores indicate better performance."
        >
            <div class="mt-3 flex flex-col">
                <div class="text-accent-foreground flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex">
                        <span class="font-semibold">Scoring Formula:</span>
                        <span>• Single Sitting → <b>A=1</b>, <b>B=2</b>, <b>C=3</b></span>
                        <span>• Multiple Sittings →</span>
                        <span> <b>Year₁:</b> A=1, B=2, C=3; <b>Year₂:</b> A=2, B=3, C=4; <b>Year₃:</b> A=3, B=4, C=5 </span>(e.t.c)
                    </div>
                    <ClassSize :class-size="classSize" />
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <div class="flex items-center gap-2 rounded-full border border-green-100 bg-white py-2 px-4">
                        <div class="h-5 w-20 rounded-full bg-green-100"></div>
                        <div class="text-[11px] uppercase text-green-600">Provisional class list</div>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-purple-100 bg-white py-2 px-4">
                        <div class="h-5 w-20 rounded-full bg-purple-100"></div>
                        <div class="text-[11px] uppercase text-purple-600">Waiting list</div>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-red-100 bg-white py-2 px-4">
                        <div class="h-5 w-20 rounded-full bg-red-100"></div>
                        <div class="text-[11px] uppercase text-red-600">Faulty applications (missing o level subjects, no payment)</div>
                    </div>
                </div>
            </div>
        </BaseAlert>
    </div>
</template>
