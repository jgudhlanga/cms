<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
</script>

<template>
    <BaseCard :title="$t('trans.documents')" :description="$t('trans.documents_description')">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            Upload documents
        </div>
    </BaseCard>
</template>
