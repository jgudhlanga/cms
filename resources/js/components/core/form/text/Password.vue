<script setup lang="ts">
import { BaseInput } from '@/components/core/form';
import { TextFieldType } from '@/enums/inputs';
</script>

<template>
    <BaseInput
        inputId="password"
        :type="TextFieldType.password"
        :label="$t('trans.password')"
        autocomplete="password"
        :placeholder="$t('trans.password')"
    />
</template>
