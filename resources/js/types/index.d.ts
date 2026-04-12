export interface User {
    id: string;
    name: string;
    display_name: string;
    email: string;
    email_verified_at?: string;
    avatar_url: string | null;
    base_currency: string;
    theme: string;
    two_factor_confirmed_at: string | null;
    current_workspace_id: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
