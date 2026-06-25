import { chromium } from 'playwright';
import { writeFileSync } from 'fs';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'dlowe@example.net';
const PASS  = 'verify123';
const SS    = (name) => `C:/Users/bry/AppData/Local/Temp/claude/c--xampp2-htdocs-ariane-gghi-hr/5aff2f31-1aed-46cc-a058-5f0b2e2911af/scratchpad/${name}.png`;

const browser = await chromium.launch({ headless: true });
const page    = await browser.newPage();
page.setDefaultTimeout(15000);

const log = [];
const step = (msg) => { console.log(msg); log.push(msg); };

// ── 1. Login ──────────────────────────────────────────────────────────────────
await page.goto(`${BASE}/login`);
await page.screenshot({ path: SS('01_login'), fullPage: true });
step('01. Login page loaded');

// Livewire Volt login form — fill email/password and submit
await page.fill('input[type="email"]', EMAIL);
await page.fill('input[type="password"]', PASS);
await page.screenshot({ path: SS('02_filled'), fullPage: true });

await page.click('button[type="submit"]');
await page.waitForURL(/dashboard/, { timeout: 10000 });
step('02. Logged in → redirected to: ' + page.url());
await page.screenshot({ path: SS('03_dashboard'), fullPage: true });

// ── 2. Navigate to /trip-ticket ───────────────────────────────────────────────
await page.goto(`${BASE}/trip-ticket`);
await page.waitForLoadState('networkidle');
const tripStatus = await page.evaluate(() => document.title);
step('03. /trip-ticket loaded — title: ' + tripStatus);
await page.screenshot({ path: SS('04_trip_form'), fullPage: true });

// Check each required field exists
const fields = {
  'destination_from input':   'input[wire\\:model\\.live="destination_from"], input[name="destination_from"]',
  'destination_to input':     'input[wire\\:model\\.live="destination_to"], input[name="destination_to"]',
  'departure_datetime input':  'input[type="datetime-local"]',
  'vehicle_id select':         'select[wire\\:model\\.live="vehicle_id"]',
  'driver_id select':          'select[wire\\:model\\.live="driver_id"]',
  'passengers textarea':       'textarea[wire\\:model\\.live="passengers"]',
  'purpose textarea':          'textarea[wire\\:model\\.live="purpose"]',
  'Submit button':             'button[wire\\:click="submit"]',
};

for (const [label, sel] of Object.entries(fields)) {
  const el = await page.$(sel);
  step(`   ${el ? '✅' : '❌'} ${label}`);
}

// ── 3. Check approval chain display ──────────────────────────────────────────
const chainText = await page.locator('text=Immediate Head').count();
step(`04. Approval chain shows "Immediate Head": ${chainText > 0 ? '✅' : '❌'}`);

// ── 4. Fill and submit a trip ticket ─────────────────────────────────────────
step('05. Filling out the trip ticket form...');

// Use wire:model.live selectors via Playwright's attribute selector
await page.locator('[wire\\:model\\.live="destination_from"]').fill('GGHI Main Campus');
await page.locator('[wire\\:model\\.live="destination_to"]').fill('DOH Regional Office');

// Set datetime-local value
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
tomorrow.setHours(8, 0, 0, 0);
const dtLocal = tomorrow.toISOString().slice(0, 16); // "2026-06-26T08:00"
await page.locator('input[type="datetime-local"]').first().fill(dtLocal);

await page.locator('[wire\\:model\\.live="purpose"]').fill('Procurement of medical supplies and attendance at seminar');

await page.screenshot({ path: SS('05_form_filled'), fullPage: true });

// Submit
await page.locator('button[wire\\:click="submit"]').click();
await page.waitForTimeout(2000); // let Livewire respond
await page.screenshot({ path: SS('06_after_submit'), fullPage: true });

// Check for success flash or error
const success = await page.locator('text=Trip ticket filed successfully').count();
const errors  = await page.locator('.text-red-500').count();
step(`06. After submit — success message: ${success > 0 ? '✅ yes' : '❌ no'}, validation errors: ${errors}`);

// ── 5. Navigate to /trip-ticket/my-tickets ────────────────────────────────────
await page.goto(`${BASE}/trip-ticket/my-tickets`);
await page.waitForLoadState('networkidle');
const myStatus = page.url();
step('07. /trip-ticket/my-tickets loaded — url: ' + myStatus);
await page.screenshot({ path: SS('07_my_tickets'), fullPage: true });

const tableVisible = await page.locator('table').count();
const noTickets    = await page.locator('text=No trip tickets found').count();
// Either a table or the empty-state message is correct
step(`08. My tickets page — table present: ${tableVisible > 0 ? '✅' : '❌'}, or empty state: ${noTickets > 0 ? '✅' : '—'}`);

// Check the just-filed ticket appears
const doh = await page.locator('text=DOH Regional Office').count();
step(`09. Filed ticket appears in list: ${doh > 0 ? '✅' : '❌'} (DOH Regional Office visible)`);

// ── 6. Probe: submit with empty purpose ───────────────────────────────────────
await page.goto(`${BASE}/trip-ticket`);
await page.waitForLoadState('networkidle');
await page.locator('button[wire\\:click="submit"]').click();
await page.waitForTimeout(1500);
const validationErrors = await page.locator('[class*="text-red"]').count();
step(`🔍 10. Empty submit shows validation errors: ${validationErrors > 0 ? '✅ yes (' + validationErrors + ' fields)' : '❌ none shown'}`);
await page.screenshot({ path: SS('08_validation'), fullPage: true });

await browser.close();
console.log('\nScreenshots saved to scratchpad.');
