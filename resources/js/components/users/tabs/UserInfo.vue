<script setup lang="ts">
import ProfileContactSection from '@/components/users/profile/ProfileContactSection.vue';
import ProfileFieldCard from '@/components/users/profile/ProfileFieldCard.vue';
import { useUserProfileDetails } from '@/composables/users/useUserProfileDetails';
import { User } from '@/types/users';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { personalFields, contactRows } = useUserProfileDetails(() => props.user);
</script>

<template>
    <div class="space-y-8">
        <section class="space-y-3">
            <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                {{ $t('trans.personal_details') }}
            </h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <ProfileFieldCard
                    v-for="field in personalFields"
                    :key="field.transKey"
                    :label="$t(field.transKey)"
                    :value="field.value"
                    :is-empty="field.isEmpty"
                    :empty-label="$t('trans.not_set')"
                />
            </div>
        </section>

        <ProfileContactSection
            :title="$tChoice('trans.contact', 1)"
            :rows="contactRows"
            :empty-label="$t('trans.not_provided')"
        />
    </div>
</template>
