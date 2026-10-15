// Recorre el panel en Chrome sin interfaz (protocolo DevTools, sin dependencias) y reporta bloqueos
// de CSP y errores de JavaScript: login, pantallas, 404, navegación SPA y los botones que usan
// data-history-back, data-auto-submit y data-reload. Sirve para activar csp.nonce en un proyecto.
//
// Requiere Google Chrome y un servidor con build.debug=false (en debug no se envía CSP), csp.enabled
// y csp.nonce en true, y un usuario superadministrador:
//   PANEL=/admin EMAIL=admin@ejemplo.cl PASS='...' DISCOVER=1 node versaWYS/tests/csp-check.mjs http://127.0.0.1:8790 /tmp/csp
// PANEL: prefijo del panel (admin.custom_url). DISCOVER=1 recorre las pantallas que enlaza el menú.
// Termina con código 1 si encuentra problemas.
import { spawn } from 'node:child_process';
import { setTimeout as sleep } from 'node:timers/promises';

const [base, tmp] = process.argv.slice(2);
const PANEL = process.env.PANEL ?? '/admin';
const EMAIL = process.env.EMAIL ?? 'admin@csp.local';
const PASS = process.env.PASS ?? 'Clave#Prueba2026';
const PORT = 9333;
const chrome = spawn('google-chrome', [
    '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    `--remote-debugging-port=${PORT}`, `--user-data-dir=${tmp}/chrome`, 'about:blank',
], { stdio: 'ignore' });

let targets;
for (let i = 0; i < 50 && !targets; i++) {
    try { targets = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json(); } catch { await sleep(200); }
}
const page = targets.find(t => t.type === 'page');
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise(r => ws.addEventListener('open', r));

let seq = 0;
const pending = new Map();
let current = [];
const loadWaiters = [];
ws.addEventListener('message', ({ data }) => {
    const msg = JSON.parse(data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result ?? msg.error); pending.delete(msg.id); return; }
    const p = msg.params ?? {};
    if (msg.method === 'Page.loadEventFired') loadWaiters.splice(0).forEach(r => r());
    if (msg.method === 'Runtime.exceptionThrown') current.push('JS: ' + (p.exceptionDetails?.exception?.description ?? p.exceptionDetails?.text).split('\n')[0]);
    if (msg.method === 'Runtime.consoleAPICalled' && p.type === 'error') current.push('console.error: ' + p.args.map(a => a.value ?? a.description ?? '').join(' ').slice(0, 160));
    if (msg.method === 'Log.entryAdded' && /Content Security Policy|Refused to/i.test(p.entry.text)) current.push('CSP: ' + p.entry.text.slice(0, 180));
});
const send = (method, params = {}) => new Promise(r => { const id = ++seq; pending.set(id, r); ws.send(JSON.stringify({ id, method, params })); });
const evaluate = async expr => (await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }))?.result?.value;
const navigate = async url => { const loaded = new Promise(r => loadWaiters.push(r)); await send('Page.navigate', { url }); await loaded; await sleep(1500); };

await send('Page.enable'); await send('Runtime.enable'); await send('Log.enable');

const report = [];
// Un módulo Vue que no monta deja una pantalla de error aunque no haya excepción en la consola
const errorScreen = `(document.querySelector('#versa-hmr-error-overlay, #error-container')?.innerText ?? '').replace(/\\s+/g, ' ').slice(0, 160)`;
const step = async (name, fn) => {
    current = [];
    await fn();
    const screen = await evaluate(errorScreen);
    if (screen) current.push('pantalla de error: ' + screen);
    // Dos builds de Vue en la misma página (archivos compilados en modo dev y prod mezclados): v-model y los
    // slots dejan de funcionar sin error visible. Se arregla compilando todo de nuevo (pnpm compile).
    const vues = await evaluate(`[...new Set(performance.getEntriesByType('resource').map(e => e.name.split('?')[0]).filter(n => /\\/vue\\.esm-browser/.test(n)))]`);
    if ((vues ?? []).length > 1) current.push('dos builds de Vue: ' + vues.join(' y '));
    current.push(`(url: ${await evaluate('location.pathname')})`);
    report.push([name, [...new Set(current)]]);
};

await step('login (carga)', () => navigate(`${base}${PANEL}/login`));
await step('login (enviar)', async () => {
    await evaluate(`document.querySelector('#email').value=${JSON.stringify(EMAIL)}; document.querySelector('#password').value=${JSON.stringify(PASS)}; document.querySelector('#login').click();`);
    for (let i = 0; i < 20 && (await evaluate('location.pathname')) === `${PANEL}/login`; i++) await sleep(300);
    await sleep(1500);
    current.push(`(alerta: ${(await evaluate("document.querySelector('#alert')?.innerText ?? ''")).slice(0, 120)})`);
});
const logged = await evaluate('location.pathname');
// Pantallas: las fijas del panel base, o las que enlaza el menú si DISCOVER=1
let pages = ['/admin/dashboard', '/admin/usuarios', '/admin/usuarios/addUser', '/admin/perfiles', '/admin/empresas',
    '/admin/modules/modulesRoutes', '/admin/configuracionRoutes', '/admin/perfiluser', '/admin/mediaStore'];
if (process.env.DISCOVER) {
    await navigate(`${base}${PANEL}/dashboard`);
    pages = await evaluate(`[...new Set([...document.querySelectorAll('a[href]')].map(a => a.getAttribute('href')).filter(h => h.startsWith(${JSON.stringify(PANEL + '/')}) && !/logout|login|download|export/.test(h)))]`);
}
// EXTRA: rutas fuera del panel separadas por coma (tienda, páginas públicas)
pages.push(...(process.env.EXTRA ?? '').split(',').filter(Boolean));
for (const path of [...pages, `${PANEL}/no-existe`]) {
    await step(path, () => navigate(base + path));
}
await step('SPA: dashboard → usuarios por el menú', async () => {
    await navigate(`${base}${PANEL}/dashboard`);
    current = [];
    const clicked = await evaluate(`(() => { const a = [...document.querySelectorAll('a[href]')].find(a => /\\/usuarios$/.test(a.getAttribute('href'))); if (!a) return 'sin enlace'; a.click(); return a.getAttribute('href'); })()`);
    await sleep(2500);
    current.push(`(clic en ${clicked}; url ahora ${await evaluate('location.pathname')})`);
});

await step('404: botón Volver (data-history-back)', async () => {
    await navigate(`${base}${PANEL}/dashboard`);
    await navigate(`${base}${PANEL}/no-existe`);
    current = [];
    const found = await evaluate(`!!document.querySelector('[data-history-back]')`);
    await evaluate(`document.querySelector('[data-history-back]')?.click()`);
    await sleep(1500);
    current.push(`(botón presente: ${found}; tras clic: ${await evaluate('location.pathname')})`);
});
await step('selector de empresa (data-auto-submit)', async () => {
    await navigate(`${base}${PANEL}/dashboard`);
    current = [];
    const info = await evaluate(`(() => { const s = document.querySelector('#selectEmpresa'); if (!s) return 'sin selector'; let sent = false; s.form.submit = () => { sent = true; }; s.dispatchEvent(new Event('change', { bubbles: true })); return 'submit llamado: ' + sent; })()`);
    current.push(`(${info})`);
});
await step('módulo Vue que falla: botón Reintentar (data-reload)', async () => {
    await navigate(`${base}${PANEL}/usuarios`);
    current = [];
    // La pantalla que el cargador muestra cuando un módulo Vue no carga
    await evaluate(`import('/public/dashboard/js/vueLoader/devUtils.js').then(m => { const c = document.querySelector('#main-content') ?? document.body; m.handleError(new Error('módulo de prueba'), 'no/existe', c); })`);
    await sleep(500);
    const hasButton = await evaluate(`!!document.querySelector('[data-reload]')`);
    await evaluate(`window.__antesDeRecargar = true; document.querySelector('[data-reload]')?.click()`);
    await sleep(2000);
    current.push(`(botón presente: ${hasButton}; recargó: ${(await evaluate('window.__antesDeRecargar')) !== true})`);
});
console.log(`sesión tras login: ${logged}`);
let total = 0;
for (const [name, problems] of report) {
    const real = problems.filter(p => !p.startsWith('(') && !p.includes('módulo de prueba'));
    total += real.length;
    console.log(`${real.length ? '✗' : '✓'} ${name}${problems.length ? '\n    ' + problems.join('\n    ') : ''}`);
}
console.log(`TOTAL problemas: ${total}`);
ws.close();
chrome.kill();
process.exit(total > 0 ? 1 : 0);
