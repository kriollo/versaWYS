import { inject, provide } from '/node_modules/vue/dist/vue.esm-browser.js';
export function createInjection(keyDesc) {
    const key = Symbol(keyDesc);
    return {
        key,
        provide: (value) => provide(key, value),
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
//# sourceMappingURL=injectStrict.js.map