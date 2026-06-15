<script setup lang="ts">
import CrumbTitle from '@/components/core/util/CrumbTitle.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Link } from '@inertiajs/vue3';

defineProps<{
    breadcrumbs: BreadcrumbItemInterface[];
}>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <!-- Mobile: show only current breadcrumb -->
            <div class="block min-w-0 md:hidden">
                <BreadcrumbItem class="min-w-0">
                    <BreadcrumbPage class="truncate font-extrabold uppercase">
                        <CrumbTitle :breadcrumb="breadcrumbs[breadcrumbs.length - 1]" />
                    </BreadcrumbPage>
                </BreadcrumbItem>
            </div>

            <!-- Desktop: show full breadcrumb trail -->
            <div class="hidden items-center md:flex">
                <template v-for="(item, index) in breadcrumbs" :key="index">
                    <BreadcrumbItem>
                        <template v-if="index === breadcrumbs.length - 1">
                            <BreadcrumbPage class="font-extrabold uppercase">
                                <CrumbTitle :breadcrumb="item" />
                            </BreadcrumbPage>
                        </template>
                        <template v-else>
                            <BreadcrumbLink class="font-extrabold uppercase" as-child>
                                <Link :href="item.href ?? '#'">
                                    <CrumbTitle :breadcrumb="item" />
                                </Link>
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                </template>
            </div>
        </BreadcrumbList>
    </Breadcrumb>
</template>
