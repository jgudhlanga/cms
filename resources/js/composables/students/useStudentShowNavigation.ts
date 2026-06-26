import {
    buildStudentShowBreadcrumbs,
    navigationOptionsFromQuery,
    parseStudentShowQuery,
    resolveStudentShowBackDestination,
    resolveStudentShowBackUrl,
    studentShowBackPermission,
    type StudentShowNavigationOptions,
    type StudentShowNavigationQuery,
} from '@/lib/studentShowNavigation';
import { hasAbility } from '@/lib/permissions';
import type { Link } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useStudentShowNavigation() {
    const page = usePage();

    const query = computed((): StudentShowNavigationQuery => {
        const searchParams = new URL(page.url, window.location.origin).searchParams;

        return parseStudentShowQuery(searchParams);
    });

    const navigationOptions = computed((): StudentShowNavigationOptions => navigationOptionsFromQuery(query.value));

    const backUrl = computed((): string => resolveStudentShowBackUrl(query.value.from, query.value.return, window.location.origin));

    const backDestination = computed((): Link => resolveStudentShowBackDestination(query.value.from));

    const breadcrumbs = computed((): Link[] => buildStudentShowBreadcrumbs(query.value.from));

    const showBack = computed((): boolean => hasAbility(studentShowBackPermission(query.value.from)));

    return {
        query,
        navigationOptions,
        backUrl,
        backDestination,
        breadcrumbs,
        showBack,
    };
}
