import { DEFAULT_AVATAR, DEFAULT_IMAGE, LOGO } from '@/lib/constants';
import { ref } from 'vue';

const defaultAvatarImage = ref(DEFAULT_AVATAR);
const defaultObjectImage = ref(DEFAULT_IMAGE);
const appLogo = ref(LOGO);

export function useDefaults() {
	return {
		defaultAvatarImage,
		defaultObjectImage,
		appLogo
	};
}
