<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import { TextFieldType } from '@/enums/inputs';

interface Props {
    label: string;
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
    />
</template>
