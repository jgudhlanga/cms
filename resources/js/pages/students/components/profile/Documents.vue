<script setup lang="ts">
import ProfileTabComingSoon from '@/components/core/util/ProfileTabComingSoon.vue';
import { IconName } from '@/lib/icons';
</script>

<template>
    <ProfileTabComingSoon
        :icon="IconName.files"
        :title="$t('students.documents_coming_soon_title')"
        :description="$t('students.documents_coming_soon_description')"
    />
</template>
