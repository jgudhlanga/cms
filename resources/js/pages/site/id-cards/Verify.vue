<script setup lang="ts">
import { LOGO } from '@/lib/constants';
import type { StudentIdCardSettings } from '@/types/id-cards';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CardPayload {
    photoUrl: string | null;
    studentName: string;
    studentNumber: string;
    department: string;
    course: string;
    expiryDate: string;
    statusLabel: string | null;
}

interface Props {
    outcome: 'valid' | 'invalid' | 'expired';
    institution: StudentIdCardSettings;
    card: CardPayload | null;
    studentProfileUrl: string | null;
}

const props = defineProps<Props>();

const statusClass = computed(() => {
    if (props.outcome === 'valid') {
        return 'bg-emerald-600 text-white';
    }

    if (props.outcome === 'expired') {
        return 'bg-amber-600 text-white';
    }

    return 'bg-red-600 text-white';
});

const statusKey = computed(() => {
    if (props.outcome === 'valid') {
        return 'trans.student_id_card_verify_valid';
    }

    if (props.outcome === 'expired') {
        return 'trans.student_id_card_verify_expired';
    }

    return 'trans.student_id_card_verify_invalid';
});
</script>

<template>
    <Head :title="$t(statusKey)" />

    <div class="mx-auto flex min-h-svh w-full max-w-lg flex-col px-4 py-8">
        <header class="mb-6 flex items-center gap-3">
            <img
                :src="institution.logoUrl || LOGO"
                alt=""
                class="size-12 rounded-full bg-white object-contain p-1"
            >
            <div>
                <p class="text-lg font-semibold">{{ institution.institutionName }}</p>
                <p v-if="institution.website" class="text-sm text-muted-foreground">{{ institution.website }}</p>
            </div>
        </header>

        <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
            <p
                class="mb-6 rounded-full px-4 py-2 text-center text-sm font-semibold tracking-wide uppercase"
                :class="statusClass"
            >
                {{ $t(statusKey) }}
            </p>

            <div v-if="outcome === 'valid' && card" class="space-y-4">
                <div class="mx-auto aspect-35/45 w-40 overflow-hidden rounded-xl border border-border bg-muted">
                    <img
                        v-if="card.photoUrl"
                        :src="card.photoUrl"
                        :alt="card.studentName"
                        class="h-full w-full object-cover"
                    >
                </div>
                <div class="space-y-1 text-center">
                    <h1 class="text-xl font-semibold">{{ card.studentName }}</h1>
                    <p class="font-mono text-sm">{{ card.studentNumber }}</p>
                    <p class="text-sm text-muted-foreground">{{ card.course || card.department }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ $t('trans.student_id_card_valid_until') }}: {{ card.expiryDate }}
                    </p>
                </div>
                <div v-if="studentProfileUrl" class="pt-2 text-center">
                    <Link
                        :href="studentProfileUrl"
                        class="inline-flex rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        {{ $t('trans.student_id_card_verify_view_profile') }}
                    </Link>
                </div>
            </div>

            <p v-else class="text-center text-sm text-muted-foreground">
                {{ $t('trans.student_id_card_verify_not_found') }}
            </p>
        </div>
    </div>
</template>
