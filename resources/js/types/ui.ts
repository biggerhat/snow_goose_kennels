export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    /** A button in the message, such as Undo. */
    action?: { label: string; url: string };
};

export type Palette = 'black' | 'chocolate' | 'yellow';
