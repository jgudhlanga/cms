export type Tone =
    | 'indigo'
    | 'emerald'
    | 'sky'
    | 'amber'
    | 'rose'
    | 'violet'
    | 'teal'
    | 'pink'
    | 'blue'
    | 'orange'
    | 'lime'
    | 'cyan'
    | 'fuchsia'
    | 'purple'
    | 'slate';

export const toneGradient: Record<Tone, string> = {
    indigo: 'bg-linear-to-r from-indigo-500 to-indigo-400',
    emerald: 'bg-linear-to-r from-emerald-500 to-emerald-400',
    sky: 'bg-linear-to-r from-sky-500 to-sky-400',
    amber: 'bg-linear-to-r from-amber-500 to-amber-400',
    rose: 'bg-linear-to-r from-rose-500 to-rose-400',
    violet: 'bg-linear-to-r from-violet-500 to-violet-400',
    teal: 'bg-linear-to-r from-teal-500 to-teal-400',
    pink: 'bg-linear-to-r from-pink-500 to-pink-400',
    blue: 'bg-linear-to-r from-blue-500 to-blue-400',
    orange: 'bg-linear-to-r from-orange-500 to-orange-400',
    lime: 'bg-linear-to-r from-lime-500 to-lime-400',
    cyan: 'bg-linear-to-r from-cyan-500 to-cyan-400',
    fuchsia: 'bg-linear-to-r from-fuchsia-500 to-fuchsia-400',
    purple: 'bg-linear-to-r from-purple-500 to-purple-400',
    slate: 'bg-linear-to-r from-slate-400 to-slate-300',
};

export const toneSolid: Record<Tone, string> = {
    indigo: 'bg-indigo-500',
    emerald: 'bg-emerald-500',
    sky: 'bg-sky-500',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    violet: 'bg-violet-500',
    teal: 'bg-teal-500',
    pink: 'bg-pink-500',
    blue: 'bg-blue-500',
    orange: 'bg-orange-500',
    lime: 'bg-lime-500',
    cyan: 'bg-cyan-500',
    fuchsia: 'bg-fuchsia-500',
    purple: 'bg-purple-500',
    slate: 'bg-slate-400',
};

export const toneChip: Record<Tone, string> = {
    indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    teal: 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
    pink: 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    orange: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
    lime: 'bg-lime-100 text-lime-700 dark:bg-lime-950 dark:text-lime-300',
    cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
    fuchsia: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
};

export const cycleTones = (tones: Tone[], index: number): Tone => tones[index % tones.length];
