export type Audit = {
    type: 'audit-trail';
    id: string;
    attributes: {
        logName: string;
        description: string;
        subjectType: string;
        subjectId: string | null;
        causerType: string | null;
        causer: string | null;
        properties: Record<string, any>;
        batchUuid: string | null;
        createdAt: string;
        updatedAt: string;
    };
};
