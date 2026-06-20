export type Ledger = {
    type: string;
    id: string | number;
    attributes: {
        feeTypeId: number | null;
        feeType: string | null;
        paymentOption: number | null;
        type: string | null;
        paymentStatus: string | null;
        amount: string | number | null;
        currency: string | null;
        clientFee: string | number | null;
        merchantFee: string | number | null;
        systemReference: string | null;
        paymentReference: string | null;
        dueDate: string | null;
        paymentDate: string | null;
        responseMessage: string | null;
        responseCode: string | null;
        levelId: number | null;
        level: string | null;
        studentProgramId: number | null;
        createdAt: string | null;
        updatedAt: string | null;
        deletedAt: string | null;
    };
};
