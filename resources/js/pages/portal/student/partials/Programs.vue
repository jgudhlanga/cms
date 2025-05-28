<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

const { department } = storeToRefs(useCreateApplicationFormStore());

const props = defineProps<{ form: InertiaForm<CreateApplicationParams> }>();
const { form } = props;
</script>

<template>
    <BaseCard>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <InstitutionDepartmentComboSelect
                :form="form"
                v-model="department"
                :error="form.errors.department"
                :label-uppercase="true"
                :is-required="true"
            />
        </div>
    </BaseCard>
</template>
