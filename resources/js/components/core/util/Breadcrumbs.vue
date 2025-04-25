<script setup lang="ts">
import {Link} from "@inertiajs/vue3"
import {
	Breadcrumb,
	BreadcrumbItem,
	BreadcrumbLink,
	BreadcrumbList,
	BreadcrumbPage,
	BreadcrumbSeparator
} from '@/components/ui/breadcrumb';
import { BreadcrumbItemInterface } from '@/types/ui';
import CrumbTitle from '@/components/core/util/CrumbTitle.vue';

defineProps<{
	breadcrumbs: BreadcrumbItemInterface[];
}>();

</script>

<template>
	<Breadcrumb>
		<BreadcrumbList>
			<template v-for="(item, index) in breadcrumbs" :key="index">
				<BreadcrumbItem>
					<template v-if="index === breadcrumbs.length - 1">
						<BreadcrumbPage class="uppercase font-extrabold">
							<CrumbTitle :breadcrumb="item" />
						</BreadcrumbPage>
					</template>
					<template v-else>
						<BreadcrumbLink class="uppercase font-extrabold" as-child>
							<Link :href="item.href ?? '#'">
								<CrumbTitle :breadcrumb="item" />
							</Link>
						</BreadcrumbLink>
					</template>
				</BreadcrumbItem>
				<BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
			</template>
		</BreadcrumbList>
	</Breadcrumb>
</template>
