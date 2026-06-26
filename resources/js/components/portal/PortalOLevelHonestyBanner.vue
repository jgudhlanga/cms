<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
</script>

<template>
    <BaseAlert
        class="mb-4"
        :type="TypeVariant.warning"
        :title="$t('trans.portal_o_level_honesty_title')"
        :description="$t('trans.portal_o_level_honesty_description')"
    />
</template>
