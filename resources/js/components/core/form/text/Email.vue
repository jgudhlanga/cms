<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import { TextFieldType } from '@/enums/inputs';
</script>

<template>
    <BaseInput
        inputId="email"
        :type="TextFieldType.email"
        :label="$t('trans.email')"
        autocomplete="email"
        :placeholder="$t('trans.ui_admin_example_com')"
    />
</template>
