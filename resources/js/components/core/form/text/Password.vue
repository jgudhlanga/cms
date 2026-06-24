<script setup lang="ts">
import PasswordInputWithToggle from '@/components/core/form/text/PasswordInputWithToggle.vue';

defineOptions({ inheritAttrs: false });
</script>

<template>
    <PasswordInputWithToggle
        input-id="password"
        :label="$t('trans.password')"
        :placeholder="$t('trans.password')"
        v-bind="$attrs"
    />
</template>
