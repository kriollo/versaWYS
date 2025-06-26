import { app } from '@/dashboard/js/vue-instancia';
import { createPinia } from '/node_modules/pinia/dist/pinia.esm-browser.js';
export const pinia = createPinia();
app.use(pinia);
//# sourceMappingURL=pinia-instancia.js.map