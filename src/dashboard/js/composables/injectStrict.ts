import { type InjectionKey, inject } from 'vue';

export const injectStrict = <T>(key: InjectionKey<T>, _fallback?: T): T => {
    const resolved = inject(key);
    if (!resolved) {
        throw new Error(`injection "${String(key)}" not found`);
    }
    return resolved;
};
