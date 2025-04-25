import { ColorVariant } from '@/enums/colors';

const colorVariants: Record<ColorVariant, string> = {
	[ColorVariant.danger]: '#ef4444',
	[ColorVariant.fuchsia]: '#A855F7',
	[ColorVariant.info]: '#3b82f6',
	[ColorVariant.primary]: '#30a8ff',
	[ColorVariant.shade]: '#64748b',
	[ColorVariant.success]: '#22c55e',
	[ColorVariant.warning]: '#fb923c',
	[ColorVariant.white]: '#FFFFFF',
	[ColorVariant.danger_outline]: '',
	[ColorVariant.fuchsia_outline]: '',
	[ColorVariant.primary_outline]: '',
	[ColorVariant.info_outline]: '',
	[ColorVariant.success_outline]: '',
	[ColorVariant.warning_outline]: '',
	[ColorVariant.shade_outline]: '',
	[ColorVariant.transparent]: ''
};

export { colorVariants };
