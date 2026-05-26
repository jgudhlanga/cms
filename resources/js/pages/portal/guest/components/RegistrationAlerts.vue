<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
</script>

<template>
    <div class="mb-4 space-y-3">
        <BaseAlert :type="TypeVariant.warning" :description="$t('trans.ui_ecocash_users_payment_device_warning')" />
        <div class="[&_div.text-sm]:text-xs [&_div.text-sm]:font-semibold [&_div.text-sm]:uppercase">
            <BaseAlert :type="TypeVariant.danger" :description="$t('trans.ui_college_advert_warning')" />
        </div>
    </div>
</template>
