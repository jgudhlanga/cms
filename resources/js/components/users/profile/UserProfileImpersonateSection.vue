<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import type { User } from '@/types/users';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();

const page = usePage();
const { navigateTo, isItTrue } = useUtils();

const showSection = computed(() => {
    const canImpersonate = isItTrue(page.props.auth.user?.attributes?.canImpersonate);
    const canBeImpersonated = isItTrue(props.user.attributes?.canBeImpersonated);
    const isImpersonating = isItTrue(page.props.auth.impersonating);
    const isSelf = page.props.auth.user?.id === props.user.id;

    return canImpersonate && canBeImpersonated && !isImpersonating && !isSelf;
});

const handleImpersonate = (): void => {
    navigateTo(route('impersonate', { id: props.user.id }));
};
</script>

<template>
    <section
        v-if="showSection"
        class="mx-2 mb-3 mt-6 rounded-xl border border-border bg-muted/30 p-4 md:mx-3"
    >
        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">
            {{ $t('trans.user_profile_impersonate_heading') }}
        </h3>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ $t('trans.user_profile_impersonate_description') }}
        </p>
        <div class="mt-4">
            <BaseButton
                classes="rounded-full"
                :variant="ColorVariant.warning_outline"
                type="button"
                @click="handleImpersonate"
            >
                {{ $t('trans.user_profile_impersonate_action') }}
            </BaseButton>
        </div>
    </section>
</template>
