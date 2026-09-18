export type ConsoleRunStatus = 'queued' | 'running' | 'succeeded' | 'failed';

export type ConsoleRisk = 'read_only' | 'mutating' | 'destructive';

export type ConsoleParamType = 'boolean' | 'integer' | 'string' | 'date' | 'select' | 'radio' | 'file';

export type ConsoleCommandParam = {
    name: string;
    type: ConsoleParamType;
    required: boolean;
    default: string | number | boolean | null;
    label: string;
    hint: string | null;
    choices: string[];
    min: number | null;
    max: number | null;
};

export type ConsoleCommandDefinition = {
    key: string;
    signature: string;
    label: string;
    summary: string;
    details: string;
    risk: ConsoleRisk;
    arguments: ConsoleCommandParam[];
    options: ConsoleCommandParam[];
};

export type ConsoleCommandGroup = {
    key: string;
    label: string;
    icon: string;
    commands: ConsoleCommandDefinition[];
};

export type ConsoleCommandRun = {
    uuid: string;
    commandKey: string;
    signature: string;
    commandLine: string;
    status: ConsoleRunStatus;
    statusLabel: string;
    statusColour: string;
    exitCode: number | null;
    error: string | null;
    durationMs: number | null;
    queuedBy: string | null;
    queuedAt: string | null;
    finishedAt: string | null;
};

export type ConsoleRunOutput = {
    status: ConsoleRunStatus;
    exitCode: number | null;
    error: string | null;
    durationMs: number | null;
    chunk: string;
    offset: number;
};
