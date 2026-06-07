<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
import { trans } from 'laravel-vue-i18n';
</script>

<template>
    <BaseAlert
        :type="TypeVariant.info"
        :description="trans('trans.maintenance_users_placeholder')"
    />
</template>
