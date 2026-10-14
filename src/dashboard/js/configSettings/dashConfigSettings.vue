<script setup lang="ts">
    import { inject, onMounted, ref, type Ref } from 'vue';

    import breadcrumb from '@/dashboard/js/components/breadcrumb.vue';
    import lineHr from '@/dashboard/js/components/lineHr.vue';
    import AdminSection from '@/dashboard/js/configSettings/sections/AdminSection.vue';
    import BuildSection from '@/dashboard/js/configSettings/sections/BuildSection.vue';
    import DatabaseSection from '@/dashboard/js/configSettings/sections/DatabaseSection.vue';
    import LoginAttemptSection from '@/dashboard/js/configSettings/sections/LoginAttemptSection.vue';
    import MailSection from '@/dashboard/js/configSettings/sections/MailSection.vue';
    import PasswordPolicySection from '@/dashboard/js/configSettings/sections/PasswordPolicySection.vue';
    import type { ConfigSettingsData } from '@/dashboard/js/configSettings/types';
    import { API_RESPONSE_CODES } from '@/dashboard/js/constants';
    import { versaAlert, versaFetch } from '@/dashboard/js/functions';
    import type { VersaParamsFetch } from '@/dashboard/types/versaTypes';

    const csrf_token = inject<Ref<string>>('csrf_token', ref('')),
        panelUrl = inject<string>('panelUrl', ''),
        tabs = [
            { id: 'mail', label: 'Correo' },
            { id: 'login', label: 'Intentos de login' },
            { id: 'password', label: 'Política de contraseñas' },
            { id: 'build', label: 'Build' },
            { id: 'admin', label: 'Admin' },
            { id: 'database', label: 'Base de datos' },
        ] as const,
        activeTab = ref<(typeof tabs)[number]['id']>('mail'),
        loading = ref(true),
        saving = ref(false),
        form = ref<ConfigSettingsData>({
            mail: { host: '', port: 465, transport: 'smtp', secure: 'tls', name_from: '', username: '', password: '' },
            login_attempt: { max: 5, time: 120 },
            auth: {
                expiration_days_password: 90,
                inactive_account_days: 30,
                password_policy: { large: 8, uppercase: true, lowercase: true, number: true, special_chars: true },
            },
            build: {
                name: '',
                version: '',
                timezone: '',
                charset: '',
            },
            admin: { custom_url: '', hide_default: false, redirect_404: false },
        }),
        breadCrumb = [
            {
                type: 'link',
                title: 'Home',
                icon: '<svg class="w-3 h-3 me-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/></svg>',
                link: `/${panelUrl}/dashboard`,
            },
            {
                type: 'text',
                title: 'Configuración',
                icon: '<svg class="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10" xmlns="http://www.w3.org/2000/svg"><path d="m1 9 4-4-4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
                link: '',
            },
        ],
        loadSettings = async () => {
            loading.value = true;
            const response = await versaFetch({
                url: `/${panelUrl}/configuracion/get`,
                method: 'GET',
            });
            if (API_RESPONSE_CODES.SUCCESS === response.success && response.data) {
                form.value = response.data as ConfigSettingsData;
            }
            loading.value = false;
        };

    onMounted(loadSettings);

    const saveSettings = async () => {
        saving.value = true;
        const params = {
                url: `/${panelUrl}/configuracion/save`,
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                data: { ...form.value, csrf_token: csrf_token.value },
            } as VersaParamsFetch,
            response = await versaFetch(params);
        saving.value = false;

        if (API_RESPONSE_CODES.ERROR === response.success) {
            const errores = Object.values(response.errors ?? { error: response.message })
                .map(msg => `<li>${msg}</li>`)
                .join('');
            versaAlert({
                title: 'Error',
                type: 'error',
                html: `<ul class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">${errores}</ul>`,
            });

            return;
        }

        versaAlert({
            title: 'Éxito',
            message: response.message,
            type: 'success',
            callback: () => {
                window.location.reload();
            },
        });
    };
</script>

<template>
    <div class="w-full h-full flex flex-col">
        <breadcrumb
            title="Configuración"
            iconSVG='<svg
                    aria-hidden="true"
                    class="w-6 h-6 text-gray-800 dark:text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" />
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>'
            :items="breadCrumb" />

        <div class="flex-1 relative shadow-md sm:rounded-lg mx-4 overflow-y-auto">
            <lineHr />

            <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="w-6 h-6 border-2 border-brand border-t-transparent rounded-full animate-spin" />
            </div>

            <template v-else>
                <div class="flex border-b dark:border-gray-700 mb-6 flex-wrap px-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            activeTab === tab.id
                                ? 'text-brand border-b-2 border-brand'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
                        ]"
                        @click="activeTab = tab.id">
                        {{ tab.label }}
                    </button>
                </div>

                <form class="space-y-6 max-w-3xl px-4" @submit.prevent="saveSettings">
                    <MailSection v-if="activeTab === 'mail'" v-model="form.mail" />
                    <LoginAttemptSection v-if="activeTab === 'login'" v-model="form.login_attempt" />
                    <PasswordPolicySection v-if="activeTab === 'password'" v-model="form.auth" />
                    <BuildSection v-if="activeTab === 'build'" v-model="form.build" />
                    <AdminSection v-if="activeTab === 'admin'" v-model="form.admin" />
                    <DatabaseSection v-if="activeTab === 'database'" />

                    <div v-if="activeTab !== 'database'" class="flex justify-end pt-4">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-brand/30 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 disabled:opacity-50">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</template>
