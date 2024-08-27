import { app } from '@/dashboard/js/vue-instancia';
import { createPinia } from 'pinia';

export const pinia = createPinia();

app.use(pinia);
