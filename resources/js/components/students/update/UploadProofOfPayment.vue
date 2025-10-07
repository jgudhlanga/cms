<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import { TextFieldType } from '@/enums/inputs';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';

interface Props {
    label: string;
    form: InertiaForm<any>;
    inputId?: string;
    handleChange: any;
}
defineProps<Props>();
</script>

<template>
    <BaseInput
        :input-id="inputId ?? 'upload'"
        classes="w-full"
        :label-uppercase="true"
        :label="label"
        :type="TextFieldType.file"
        @change="handleChange"
        @input="clearFormErrors(form, 'proof_of_payment')"
        :error="form.errors.proof_of_payment"
    />
</template>
