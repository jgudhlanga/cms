<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';

defineProps<{ classSize: string | number }>();
</script>

<template>
    <div class="flex flex-col">
        <BaseAlert :type="TypeVariant.info" :description="$t('trans.ui_each_student_s_score_is_derived_from_their_exam_results_usin')">
            <div class="mt-3 flex flex-col">
                <div class="text-accent-foreground flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex">
                        <span class="font-semibold">{{ $t('trans.ui_scoring_formula') }}</span>
                        <span
                            >{{ $t('trans.ui_single_sitting') }}<b>{{ $t('trans.ui_a_1') }}</b
                            >, <b>{{ $t('trans.ui_b_2') }}</b
                            >, <b>{{ $t('trans.ui_c_3') }}</b></span
                        >
                        <span>{{ $t('trans.ui_multiple_sittings') }}</span>
                        <span>
                            <b>{{ $t('trans.ui_year') }}</b> {{ $t('trans.ui_a_1_b_2_c_3') }}<b>{{ $t('trans.ui_year_2') }}</b>
                            {{ $t('trans.ui_a_2_b_3_c_4') }}<b>{{ $t('trans.ui_year_3') }}</b> {{ $t('trans.ui_a_3_b_4_c_5') }}</span
                        >{{ $t('trans.ui_e_t_c') }}
                    </div>
                    <ClassSize :class-size="classSize" />
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <div class="flex items-center gap-2 rounded-full border border-green-100 bg-white px-4 py-2">
                        <div class="h-5 w-20 rounded-full bg-green-100"></div>
                        <div class="text-[11px] text-green-600 uppercase">{{ $t('trans.ui_provisional_class_list') }}</div>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-purple-100 bg-white px-4 py-2">
                        <div class="h-5 w-20 rounded-full bg-purple-100"></div>
                        <div class="text-[11px] text-purple-600 uppercase">{{ $t('trans.ui_waiting_list') }}</div>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-red-100 bg-white px-4 py-2">
                        <div class="h-5 w-20 rounded-full bg-red-100"></div>
                        <div class="text-[11px] text-red-600 uppercase">
                            {{ $t('trans.ui_faulty_applications_missing_o_level_subjects_no_payment') }}
                        </div>
                    </div>
                </div>
            </div>
        </BaseAlert>
    </div>
</template>
