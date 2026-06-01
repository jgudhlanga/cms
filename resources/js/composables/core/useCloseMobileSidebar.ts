import { useSidebar } from '@/components/ui/sidebar';

export function useCloseMobileSidebar() {
	const { isMobile, setOpenMobile } = useSidebar();

	return function closeMobileSidebar(): void {
		if (isMobile.value) {
			setOpenMobile(false);
		}
	};
}
