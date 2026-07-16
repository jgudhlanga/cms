<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { TypeVariant } from '@/enums/type-variants';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

type Candidate = {
    candidateNumber: string;
    surname: string | null;
    firstNames: string | null;
    discipline: string | null;
};

type ResultRow = {
    id: number;
    discipline: string | null;
    courseCode: string | null;
    candidateNumber: string;
    surname: string | null;
    firstNames: string | null;
    subjectCode: string | null;
    subject: string | null;
    grade: string | null;
    session: string | null;
    courseComment: string | null;
};

const props = defineProps<{
    candidate: Candidate;
    results: ResultRow[];
    canImport: boolean;
}>();

const breadcrumbs = computed<Link[]>(() => [
    { transChoiceKey: 'examinations.title', href: route('examinations.index') },
    { title: props.candidate.candidateNumber },
]);

const fullName = computed(() =>
    [props.candidate.surname, props.candidate.firstNames].filter(Boolean).join(', '),
);
</script>

<template>
    <Head :title="candidate.candidateNumber" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('examinations.index')">
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold uppercase">{{ candidate.candidateNumber }}</h2>
                <p v-if="fullName" class="text-sm text-muted-foreground">{{ fullName }}</p>
            </div>
        </template>

        <div class="space-y-4">
            <BaseAlert :type="TypeVariant.info" :description="$t('examinations.read_only_notice')" />

            <div class="overflow-x-auto rounded-md border">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">Discipline</th>
                            <th class="px-3 py-2 font-medium">Course Code</th>
                            <th class="px-3 py-2 font-medium">Candidate_Number</th>
                            <th class="px-3 py-2 font-medium">Surname</th>
                            <th class="px-3 py-2 font-medium">First_Names</th>
                            <th class="px-3 py-2 font-medium">Subject Code</th>
                            <th class="px-3 py-2 font-medium">Subject</th>
                            <th class="px-3 py-2 font-medium">Grade</th>
                            <th class="px-3 py-2 font-medium">Session</th>
                            <th class="px-3 py-2 font-medium">Course Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in results" :key="row.id" class="border-t">
                            <td class="px-3 py-2">{{ row.discipline }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.courseCode }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.candidateNumber }}</td>
                            <td class="px-3 py-2">{{ row.surname }}</td>
                            <td class="px-3 py-2">{{ row.firstNames }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.subjectCode }}</td>
                            <td class="px-3 py-2">{{ row.subject }}</td>
                            <td class="px-3 py-2 font-semibold">{{ row.grade }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.session }}</td>
                            <td class="px-3 py-2">{{ row.courseComment }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PageContainer>
</template>
