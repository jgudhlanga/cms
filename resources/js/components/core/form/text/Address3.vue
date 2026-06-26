<script setup lang="ts">
import { ADDRESS_FIELDS } from '@/lib/addressFields';
import BaseInput from './BaseInput.vue';

const field = ADDRESS_FIELDS[2];
</script>

<template>
    <BaseInput
        input-id="address_3"
        :label="$t(field.labelKey)"
        :placeholder="$t(field.placeholderKey)"
        v-bind="$attrs"
    />
</template>
