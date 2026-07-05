import { chromium } from 'playwright';

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8123';
const GATEWAY_TOKEN = process.env.GATEWAY_TOKEN || 'change-me';
const ADMIN_EMAIL = 'nh@overcloud.us';
const ADMIN_PASSWORD = 'XViSNDnR9GVZ';

function fail(message) {
    console.error(`\n❌ E2E FAILED: ${message}\n`);
    process.exit(1);
}

async function main() {
    // --- Anti-generic checks against raw HTML (before login) ---
    const loginHtml = await (await fetch(`${BASE_URL}/login`)).text();
    if (loginHtml.includes('Laravel')) fail('The /login page still mentions "Laravel".');
    if (!loginHtml.includes('NH')) fail('The /login page does not show the "NH" brand name.');

    const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
    const page = await browser.newPage();

    try {
        // --- Login ---
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
        await page.fill('#email', ADMIN_EMAIL);
        await page.fill('#password', ADMIN_PASSWORD);
        await page.click('form button:not([type="button"])');
        await page.waitForURL(/\/dashboard/, { timeout: 15000 });

        const dashboardUrl = page.url();
        if (!dashboardUrl.includes('/dashboard')) fail(`Login did not land on /dashboard (got ${dashboardUrl}).`);

        const dashboardHtml = await page.content();
        if (dashboardHtml.includes('Laravel')) fail('The dashboard still mentions "Laravel".');
        if (dashboardHtml.includes("You're logged in")) fail('The dashboard still shows the generic Breeze "You\'re logged in" text.');
        if (!dashboardHtml.includes('NH')) fail('The dashboard does not show the "NH" brand name.');
        console.log('✅ Login works and dashboard is branded as NH.');

        // --- Planes CRUD: create via UI, confirm in table, confirm persists after reload ---
        const nombrePlan = `Elite ${Date.now()}`;
        await page.goto(`${BASE_URL}/planes/create`, { waitUntil: 'domcontentloaded' });
        await page.fill('#nombre', nombrePlan);
        await page.fill('#precio', '777');
        await page.fill('#descripcion', 'Membresía de prueba E2E');
        await page.click('form button:not([type="button"])');
        await page.waitForURL(/\/planes$/, { timeout: 15000 });
        let bodyText = await page.textContent('body');
        if (!bodyText.includes(nombrePlan)) fail(`New membership "${nombrePlan}" did not appear in the Planes table after creation.`);
        await page.reload({ waitUntil: 'domcontentloaded' });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(nombrePlan)) fail(`Membership "${nombrePlan}" did not persist after reloading Planes.`);
        console.log('✅ Planes: create + persist works.');

        // --- Clientes CRUD ---
        const nombreCliente = `Cliente E2E ${Date.now()}`;
        const telefonoCliente = `521550${Date.now().toString().slice(-7)}`;
        await page.goto(`${BASE_URL}/clientes/create`, { waitUntil: 'domcontentloaded' });
        await page.fill('#nombre', nombreCliente);
        await page.fill('#telefono', telefonoCliente);
        await page.click('form button:not([type="button"])');
        await page.waitForURL(/\/clientes$/, { timeout: 15000 });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(nombreCliente)) fail(`New member "${nombreCliente}" did not appear in the Clientes table after creation.`);
        await page.reload({ waitUntil: 'domcontentloaded' });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(nombreCliente)) fail(`Member "${nombreCliente}" did not persist after reloading Clientes.`);
        console.log('✅ Clientes: create + persist works.');

        // --- Pedidos CRUD ---
        const nombrePedido = `Inscripción E2E ${Date.now()}`;
        const telefonoPedido = `521551${Date.now().toString().slice(-7)}`;
        await page.goto(`${BASE_URL}/pedidos/create`, { waitUntil: 'domcontentloaded' });
        await page.fill('#cliente', nombrePedido);
        await page.fill('#telefono', telefonoPedido);
        await page.click('form button:not([type="button"])');
        await page.waitForURL(/\/pedidos$/, { timeout: 15000 });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(nombrePedido)) fail(`New inscripción "${nombrePedido}" did not appear in the Pedidos table after creation.`);
        await page.reload({ waitUntil: 'domcontentloaded' });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(nombrePedido)) fail(`Inscripción "${nombrePedido}" did not persist after reloading Pedidos.`);
        console.log('✅ Pedidos: create + persist works.');

        // --- WhatsApp webhook: an inbound message must trigger a bot reply ---
        const waPhone = `521552${Date.now().toString().slice(-7)}`;
        const webhookRes = await fetch(`${BASE_URL}/api/wa/inbound`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'x-gateway-token': GATEWAY_TOKEN },
            body: JSON.stringify({ from: waPhone, fromName: 'Prospecto E2E', text: 'hola' }),
        });
        if (!webhookRes.ok) fail(`POST /api/wa/inbound returned HTTP ${webhookRes.status}.`);
        const webhookBody = await webhookRes.json();
        if (!webhookBody.ok) fail(`POST /api/wa/inbound did not return {ok:true} (got ${JSON.stringify(webhookBody)}).`);

        // Unauthorized requests (wrong/missing token) must be rejected.
        const unauthorizedRes = await fetch(`${BASE_URL}/api/wa/inbound`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'x-gateway-token': 'wrong-token' },
            body: JSON.stringify({ from: waPhone, text: 'hola' }),
        });
        if (unauthorizedRes.status !== 401) fail(`POST /api/wa/inbound with a bad token should return 401 (got ${unauthorizedRes.status}).`);

        // Confirm the bot actually processed the message: it must show up as a conversation
        // in "Contactos" with the "choosing membership" step the sales flow sets on first contact.
        await page.goto(`${BASE_URL}/contactos?buscar=${encodeURIComponent(waPhone)}`, { waitUntil: 'domcontentloaded' });
        bodyText = await page.textContent('body');
        if (!bodyText.includes(waPhone)) fail(`Contact "${waPhone}" created by the webhook did not appear in Contactos.`);
        if (!bodyText.includes('Eligiendo membresía')) fail('The bot did not advance the conversation to "Eligiendo membresía" after the inbound "hola".');
        console.log('✅ WhatsApp webhook: inbound message triggers a real bot reply and is tracked in Contactos.');

        console.log('\n🎉 All E2E checks passed.\n');
    } finally {
        await browser.close();
    }
}

main().catch((err) => {
    console.error(err);
    fail(err.message || String(err));
});
