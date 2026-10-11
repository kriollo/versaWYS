/* eslint-disable no-redeclare unified-signatures*/
/**
 * Parche de tipos para Vue 3
 * Este archivo soluciona problemas donde TypeScript no puede resolver automáticamente
 * las exportaciones re-exportadas de los módulos de Vue
 */

declare module 'vue' {
    // Tipos del sistema de reactividad
    export function ref<T = any>(value: T): Ref<T>;
    export function reactive<T extends object>(target: T): UnwrapNestedRefs<T>;
    export function computed<T>(getter: () => T): ComputedRef<T>;
    export function readonly<T extends object>(target: T): DeepReadonly<UnwrapNestedRefs<T>>;
    export function watch<T>(
        source: WatchSource<T> | object,
        cb: WatchCallback<T>,
        options?: WatchOptions,
    ): WatchStopHandle;
    export function watchEffect(effect: WatchEffect, options?: WatchOptionsBase): WatchStopHandle;

    // Sistema de inyección/provisión
    export function inject<T>(key: InjectionKey<T> | string): T | undefined;
    export function inject<T>(key: InjectionKey<T> | string, defaultValue: T, treatDefaultAsFactory?: false): T;
    export function inject<T>(
        key: InjectionKey<T> | string,
        defaultValue: T | (() => T),
        treatDefaultAsFactory: true,
    ): T;
    export function provide<T>(key: InjectionKey<T> | string, value: T): void;

    // Hooks de ciclo de vida
    export function onMounted(hook: () => any): void;
    export function onUnmounted(hook: () => any): void;
    export function onBeforeMount(hook: () => any): void;
    export function onBeforeUnmount(hook: () => any): void;
    export function onUpdated(hook: () => any): void;
    export function onBeforeUpdate(hook: () => any): void;
    export function onActivated(hook: () => any): void;
    export function onDeactivated(hook: () => any): void;
    export function onErrorCaptured(hook: ErrorCapturedHook): void;
    export function onRenderTracked(hook: DebuggerHook): void;
    export function onRenderTriggered(hook: DebuggerHook): void;
    export function onServerPrefetch(hook: () => Promise<any>): void;

    // Utilidades de reactividad
    export function isRef<T>(r: Ref<T> | unknown): r is Ref<T>;
    export function isReactive(value: unknown): boolean;
    export function isReadonly(value: unknown): boolean;
    export function isProxy(value: unknown): boolean;
    export function toRef<T extends object, K extends keyof T>(object: T, key: K): ToRef<T[K]>;
    export function toRefs<T extends object>(object: T): ToRefs<T>;
    export function unref<T>(ref: T | Ref<T>): T;
    export function toValue<T>(source: T | Ref<T> | (() => T)): T;
    export function shallowRef<T = any>(value: T): ShallowRef<T>;
    export function shallowReactive<T extends object>(target: T): ShallowReactive<T>;
    export function shallowReadonly<T extends object>(target: T): Readonly<T>;
    export function triggerRef(ref: Ref): void;
    export function customRef<T>(factory: CustomRefFactory<T>): Ref<T>;
    export function proxyRefs<T extends object>(objectWithRefs: T): ShallowUnwrapRef<T>;
    export function markRaw<T extends object>(value: T): Raw<T>;
    export function toRaw<T>(observed: T): T;

    // Tipos de watchers
    export function onWatcherCleanup(cleanupFn: () => void): void;

    // API de componentes
    export function defineComponent(options: ComponentOptions): any;
    export function defineAsyncComponent(source: AsyncComponentLoader | AsyncComponentOptions): Component;
    export function createApp(rootComponent: Component, rootProps?: Data | null): App;
    export function nextTick(): Promise<void>;
    export function nextTick<T, R>(this: T, fn: (this: T) => R | Promise<R>): Promise<R>;

    // Funciones de renderizado
    export function h(type: string | Component, props?: object | null, children?: unknown): VNode;
    export const Fragment: symbol;
    export const Text: symbol;
    export const Comment: symbol;

    // Componentes built-in
    export const Transition: Component;
    export const TransitionGroup: Component;
    export const KeepAlive: Component;
    export const Teleport: Component;
    export const Suspense: Component;

    // Composition API adicional
    export function getCurrentInstance(): ComponentInternalInstance | null;
    export function useSlots(): Slots;
    export function useAttrs(): Data;
    export function useCssModule(name?: string): Record<string, string>;
    export function useCssVars(getter: (ctx: any) => Record<string, string>): void;

    // Tipos auxiliares para defineEmits
    // eslint-disable-next-line @typescript-eslint/prefer-function-type
    type EmitFn<T extends Record<string, any>> = <K extends keyof T>(
        event: K,
        ...args: T[K] extends (...args: infer P) => any ? P : any[]
    ) => void;

    type EmitsArrayToObject<T extends readonly string[]> = Record<T[number], (...args: unknown[]) => void>;

    // Script setup macros (aunque TypeScript no los procesa directamente)
    export function defineProps<T>(props?: T): T;

    // DefineEmits con múltiples sobrecargas para soportar diferentes formas de uso
    export function defineEmits<T extends Record<string, any>>(): EmitFn<T>;
    export function defineEmits<T extends readonly string[]>(emitOptions: T): EmitFn<EmitsArrayToObject<T>>;
    export function defineEmits<T extends Record<string, any>>(emitOptions: T): EmitFn<T>;

    export function defineExpose<T extends Record<string, any>>(exposed?: T): void;
    export function withDefaults<T>(props: T, defaults: Partial<T>): T;
    export function defineOptions(options: ComponentOptions): void;
    export function defineSlots<T extends Record<string, Slot>>(): StrictUnwrapSlotsType<SlotsType<T>>;
    export function defineModel<T>(options?: {
        required?: boolean;
        default?: T;
        type?: PropType<T>;
        validator?: (value: unknown) => boolean;
    }): Ref<T>;

    // Re-exportar todos los tipos necesarios (sin importaciones, solo declarations)
    export type {
        App,
        AsyncComponentLoader,
        AsyncComponentOptions,
        Component,
        ComponentInternalInstance,
        ComponentOptions,
        ComputedRef,
        CustomRefFactory,
        Data,
        DebuggerHook,
        DeepReadonly,
        ErrorCapturedHook,
        InjectionKey,
        PropType,
        Raw,
        Ref,
        ShallowReactive,
        ShallowRef,
        Slot,
        Slots,
        SlotsType,
        StrictUnwrapSlotsType,
        ToRef,
        ToRefs,
        UnwrapNestedRefs,
        VNode,
        WatchCallback,
        WatchEffect,
        WatchOptions,
        WatchOptionsBase,
        WatchSource,
        WatchStopHandle,
    };
}
