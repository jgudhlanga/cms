export type CommunicationMethod = {
    type?: string,
    id?: string,
    attributes: {
        title: string,
        createdAt?: string,
        updatedAt?: string,
        deletedAt?: string,
    },
}

export type CommunicationMethodParams = {
    title: string,
}
