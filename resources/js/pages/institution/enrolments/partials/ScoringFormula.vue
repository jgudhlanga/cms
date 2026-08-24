<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
</script>

<template>
    <div class="flex flex-col">
        <BaseAlert :type="TypeVariant.info" :description="$t('trans.ui_each_student_s_score_is_derived_from_their_exam_results_usin')">
            <div class="mt-3 text-accent-foreground flex flex-wrap gap-x-1 text-xs">
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
        </BaseAlert>
    </div>
</template>
