import type { ChartOptions } from 'chart.js';

export const themeColor = (token: string, fallback: string): string => {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement).getPropertyValue(token).trim();

    return value ? `hsl(${value})` : fallback;
};

export const mutedColor = (): string => themeColor('--muted-foreground', '#64748B');
export const gridColor = (): string => themeColor('--border', '#E2E8F0');

/** Palette matching the DataRow/MetricCard tones so canvas charts read as one system. */
export const chartPalette = [
    'rgba(99, 102, 241, 0.85)',
    'rgba(16, 185, 129, 0.85)',
    'rgba(14, 165, 233, 0.85)',
    'rgba(245, 158, 11, 0.85)',
    'rgba(244, 63, 94, 0.85)',
    'rgba(139, 92, 246, 0.85)',
    'rgba(20, 184, 166, 0.85)',
    'rgba(236, 72, 153, 0.85)',
    'rgba(249, 115, 22, 0.85)',
    'rgba(6, 182, 212, 0.85)',
];

const tickFont = { size: 10 };

export const baseAxisOptions = () => ({
    y: {
        beginAtZero: true,
        border: { display: false },
        ticks: { color: mutedColor(), font: tickFont, padding: 4 },
        grid: { color: gridColor(), drawTicks: false },
    },
    x: {
        border: { display: false },
        ticks: { color: mutedColor(), font: tickFont, padding: 4 },
        grid: { display: false },
    },
});

export const baseTooltip = () => ({
    backgroundColor: themeColor('--popover', '#0F172A'),
    titleColor: themeColor('--popover-foreground', '#F8FAFC'),
    bodyColor: themeColor('--popover-foreground', '#F8FAFC'),
    borderColor: gridColor(),
    borderWidth: 1,
    padding: 8,
    cornerRadius: 6,
    boxPadding: 4,
    titleFont: { size: 11 },
    bodyFont: { size: 11 },
});

export const legendOptions = (position: 'top' | 'right' | 'bottom' | 'left' = 'bottom') => ({
    position,
    labels: {
        color: mutedColor(),
        font: tickFont,
        boxWidth: 8,
        boxHeight: 8,
        usePointStyle: true,
        pointStyle: 'circle' as const,
        padding: 10,
    },
});

export const doughnutOptions = (position: 'top' | 'right' | 'bottom' | 'left' = 'right'): ChartOptions<'doughnut'> => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: legendOptions(position),
        tooltip: baseTooltip(),
    },
});
