declare module 'CurrentUserTypes' {
    interface CurrentUser {
        id: number;
        name: string;
        email: string;
        avatar: string;
        roles: string[];
    }

    export { CurrentUser };
}
