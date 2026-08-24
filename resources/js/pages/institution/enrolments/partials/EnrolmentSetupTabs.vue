<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

interface Props {
    departmentId: number;
    active: 'offerings' | 'requirements' | 'class-sizes';
}

defineProps<Props>();
</script>

<template>
    <nav class="flex flex-wrap gap-1 rounded-lg border border-border bg-muted/20 p-1">
        <Link
            :href="route('application-offerings.show', departmentId)"
            preserve-scroll
            :class="
                cn(
                    'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                    active === 'offerings' ? 'bg-card text-primary shadow-sm ring-1 ring-primary/20' : 'text-muted-foreground hover:bg-muted/60',
                )
            "
        >
            {{ $t('application_requirements.tab_offerings') }}
        </Link>
        <Link
            :href="route('application-requirements.department', departmentId)"
            preserve-scroll
            :class="
                cn(
                    'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                    active === 'requirements' ? 'bg-card text-primary shadow-sm ring-1 ring-primary/20' : 'text-muted-foreground hover:bg-muted/60',
                )
            "
        >
            {{ $t('application_requirements.tab_requirements') }}
        </Link>
        <Link
            :href="route('application-requirements.class-sizes', departmentId)"
            preserve-scroll
            :class="
                cn(
                    'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                    active === 'class-sizes' ? 'bg-card text-primary shadow-sm ring-1 ring-primary/20' : 'text-muted-foreground hover:bg-muted/60',
                )
            "
        >
            {{ $t('application_requirements.tab_class_sizes') }}
        </Link>
    </nav>
</template>
