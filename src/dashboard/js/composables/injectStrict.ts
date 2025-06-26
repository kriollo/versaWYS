import { type InjectionKey, inject, provide } from 'vue';

export function createInjection<T>(keyDesc: string) {
    const key: InjectionKey<T> = Symbol(keyDesc);
    return {
        key,
        provide: (value: T) => provide(key, value),
        inject: () => {
            const injected = inject(key);
            if (!injected) {
                throw new Error(`[Injection] "${keyDesc}" not provided`);
            }
            return injected;
        },
        tryInject: () => inject(key), // versión opcional que puede ser null
    };
}
