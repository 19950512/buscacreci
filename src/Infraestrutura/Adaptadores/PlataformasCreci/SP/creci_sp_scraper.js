/**
 * CRECI SP - Headless Chrome scraper helper
 *
 * Uses a real Chrome browser with stealth measures to bypass Enterprise reCAPTCHA
 * on CRECI SP's broker search page.
 *
 * Usage: node creci_sp_scraper.js <creci_number> <tipo_creci>
 * Example: node creci_sp_scraper.js 123546 F
 *
 * Output: JSON on stdout
 *   Success: { "success": true, "data": { ... } }
 *   Error:   { "success": false, "error": "..." }
 */

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

puppeteer.use(StealthPlugin());

const SEARCH_URL = 'https://www.crecisp.gov.br/cidadao/buscaporcorretores';
const CHROME_PATH = process.env.CHROME_PATH || '/usr/bin/google-chrome';
const MAX_RETRIES = 3;

function output(obj) {
    process.stdout.write(JSON.stringify(obj));
}

/** Random integer between min and max (inclusive) */
function rand(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

/** Sleep for ms milliseconds */
function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

/**
 * Move mouse to an element with human-like curve, then optionally click.
 * Uses multiple small steps to simulate natural mouse movement.
 */
async function humanMove(page, selector, click = false) {
    const el = await page.$(selector);
    if (!el) throw new Error(`Element not found: ${selector}`);

    const box = await el.boundingBox();
    if (!box) throw new Error(`No bounding box for: ${selector}`);

    // Target is a random point within the element
    const targetX = box.x + rand(5, Math.max(6, box.width - 5));
    const targetY = box.y + rand(3, Math.max(4, box.height - 3));

    // Get current mouse position (start from a random spot on first call)
    const steps = rand(8, 18);
    await page.mouse.move(targetX, targetY, { steps });
    await sleep(rand(100, 300));

    if (click) {
        await page.mouse.click(targetX, targetY, { delay: rand(50, 120) });
    }
}

async function scrapeAttempt(creciNumber, tipoCreci) {
    let browser;

    try {
        browser = await puppeteer.launch({
            headless: 'new',
            executablePath: CHROME_PATH,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-extensions',
                '--disable-default-apps',
                '--no-first-run',
                '--disable-blink-features=AutomationControlled',
                '--window-size=1366,768',
                '--lang=pt-BR,pt',
            ],
            timeout: 30000,
        });

        const page = await browser.newPage();

        await page.setExtraHTTPHeaders({
            'Accept-Language': 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        });
        await page.setViewport({ width: 1366, height: 768 });

        // --- Step 1: Visit the homepage first to build cookies & reCAPTCHA trust ---
        await page.goto('https://www.crecisp.gov.br/', {
            waitUntil: 'networkidle2',
            timeout: 30000,
        });
        await sleep(rand(1500, 3000));

        // Simulate some idle mouse movements on homepage
        await page.mouse.move(rand(200, 600), rand(200, 400), { steps: rand(5, 10) });
        await sleep(rand(500, 1000));
        await page.mouse.move(rand(400, 800), rand(300, 500), { steps: rand(5, 10) });
        await sleep(rand(500, 1500));

        // --- Step 2: Navigate to the search page (like a real user clicking a link) ---
        await page.goto(SEARCH_URL, { waitUntil: 'networkidle2', timeout: 30000 });
        await sleep(rand(1000, 2500));

        // --- Step 3: Simulate reading the page - random mouse movements ---
        await page.mouse.move(rand(300, 700), rand(150, 350), { steps: rand(5, 12) });
        await sleep(rand(400, 800));

        // --- Step 4: Click on the CRECI input field naturally, then type ---
        await humanMove(page, '#RegisterNumber', true);
        await sleep(rand(300, 700));

        // Type the CRECI number with human-like random delays per keystroke
        for (const char of creciNumber) {
            await page.keyboard.type(char, { delay: rand(60, 180) });
            if (Math.random() < 0.15) await sleep(rand(100, 400)); // occasional pause
        }

        await sleep(rand(800, 1500));

        // --- Step 5: Wait for Enterprise reCAPTCHA to be fully loaded ---
        await page.waitForFunction(() => {
            return typeof grecaptcha !== 'undefined' &&
                   grecaptcha.enterprise &&
                   typeof grecaptcha.enterprise.execute === 'function';
        }, { timeout: 15000 });

        await sleep(rand(500, 1500));

        // Move mouse around a bit before clicking submit (simulates user looking at the form)
        await page.mouse.move(rand(500, 900), rand(300, 500), { steps: rand(5, 10) });
        await sleep(rand(300, 800));

        // --- Step 6: Click the submit button and let reCAPTCHA handle everything ---
        // The button has class g-recaptcha with data-callback="onSubmit".
        // Clicking it triggers reCAPTCHA's built-in handler which:
        //   1. Runs invisible reCAPTCHA assessment with browser signals
        //   2. Generates token
        //   3. Calls onSubmit(token) which sets ReCAPTCHAToken & submits form
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }),
            humanMove(page, 'button.g-recaptcha', true),
        ]);

        // --- Step 7: Check for captcha validation error ---
        const pageText = await page.evaluate(() => document.body.innerText);
        if (pageText.includes('Validação reCAPTCHA') || pageText.includes('erro na validação do capatcha')) {
            throw new Error('CAPTCHA_FAILED');
        }

        // --- Step 8: Find broker in results ---
        const registerNumberQuery = `${creciNumber}-${tipoCreci}`;
        const brokerFound = await page.evaluate((query) => {
            const forms = document.querySelectorAll('form[action*="corretordetalhes"]');
            for (const form of forms) {
                if (form.action.includes(query)) {
                    return true;
                }
            }
            return forms.length > 0;
        }, registerNumberQuery);

        if (!brokerFound) {
            const brokerFromList = await page.evaluate(() => {
                const h6s = document.querySelectorAll('.broker-details h6');
                if (h6s.length > 0) {
                    return { name: h6s[0].textContent.trim() };
                }
                return null;
            });

            if (brokerFromList) {
                return {
                    success: true,
                    data: {
                        inscricao: creciNumber,
                        nomeCompleto: brokerFromList.name,
                        situacao: 'Desconhecido',
                        cidade: '',
                        estado: 'SP',
                        dataInscricao: '',
                    },
                };
            }
            throw new Error(`CRECI ${creciNumber}-${tipoCreci} not found in search results`);
        }

        // --- Step 9: Navigate to detail page ---
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }),
            page.evaluate((query) => {
                const forms = document.querySelectorAll('form[action*="corretordetalhes"]');
                for (const form of forms) {
                    if (form.action.includes(query)) {
                        form.submit();
                        return;
                    }
                }
                if (forms.length > 0) forms[0].submit();
            }, registerNumberQuery),
        ]);

        // --- Step 10: Extract detail data ---
        const detailData = await page.evaluate(() => {
            const data = {};
            const bodyText = document.body.innerText;
            const lines = bodyText.split('\n').map(l => l.trim()).filter(l => l.length > 0);

            let nameIndex = lines.findIndex(l => l.includes('Detalhes do'));
            if (nameIndex >= 0 && nameIndex + 1 < lines.length) {
                data.nomeCompleto = lines[nameIndex + 1];
            }

            const creciLine = lines.find(l => l.startsWith('CRECI:'));
            if (creciLine) data.creci = creciLine.replace('CRECI:', '').trim();

            const dataLine = lines.find(l => l.startsWith('Data de Inscrição:'));
            if (dataLine) data.dataInscricao = dataLine.replace('Data de Inscrição:', '').trim();

            const situacaoLine = lines.find(l => l.startsWith('Situação:'));
            if (situacaoLine) data.situacao = situacaoLine.replace('Situação:', '').trim();

            const emailLine = lines.find(l => l.startsWith('E-Mail Oficial:'));
            if (emailLine) data.email = emailLine.replace('E-Mail Oficial:', '').trim();

            return data;
        });

        if (!detailData.nomeCompleto) {
            throw new Error('Could not extract broker name from detail page');
        }

        const inscricao = detailData.creci
            ? detailData.creci.replace(/[^0-9]/g, '')
            : creciNumber;

        return {
            success: true,
            data: {
                inscricao,
                nomeCompleto: detailData.nomeCompleto,
                situacao: detailData.situacao || 'Desconhecido',
                cidade: '',
                estado: 'SP',
                dataInscricao: detailData.dataInscricao || '',
            },
        };

    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

async function scrapeWithRetry(creciNumber, tipoCreci) {
    let lastError;

    for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
        try {
            return await scrapeAttempt(creciNumber, tipoCreci);
        } catch (err) {
            lastError = err;
            if (err.message === 'CAPTCHA_FAILED' && attempt < MAX_RETRIES) {
                // Increasing backoff between retries
                const delay = attempt * 8000 + rand(2000, 5000);
                await sleep(delay);
                continue;
            }
            break;
        }
    }

    throw new Error(
        lastError.message === 'CAPTCHA_FAILED'
            ? `Captcha validation failed after ${MAX_RETRIES} attempts`
            : lastError.message
    );
}

// Main
const args = process.argv.slice(2);
if (args.length < 2) {
    output({ success: false, error: 'Usage: node creci_sp_scraper.js <creci_number> <tipo_creci>' });
    process.exit(1);
}

const [creciNumber, tipoCreci] = args;

scrapeWithRetry(creciNumber, tipoCreci)
    .then(result => {
        output(result);
        process.exit(0);
    })
    .catch(err => {
        output({ success: false, error: err.message });
        process.exit(1);
    });
