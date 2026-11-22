/** The anchor for a how-to on the Help page, from its question: "Plan a pairing" becomes "topic-plan-a-pairing". */
export function topicId(question: string): string {
    return (
        'topic-' +
        question
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '')
    );
}
