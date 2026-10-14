export interface ConfigSettingsData {
    mail: {
        host: string;
        port: number;
        transport: string;
        secure: string;
        name_from: string;
        username: string;
        password: string;
    };
    login_attempt: {
        max: number;
        time: number;
    };
    auth: {
        expiration_days_password: number;
        inactive_account_days: number;
        password_policy: {
            large: number;
            uppercase: boolean;
            lowercase: boolean;
            number: boolean;
            special_chars: boolean;
        };
    };
    build: {
        name: string;
        version: string;
        timezone: string;
        charset: string;
    };
    admin: {
        custom_url: string;
        hide_default: boolean;
        redirect_404: boolean;
    };
}
