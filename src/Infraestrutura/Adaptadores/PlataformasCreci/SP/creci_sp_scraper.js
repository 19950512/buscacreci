/**
 * CRECI SP - Headless Chrome scraper helper
 * 
 * This script is called by the PHP SP scraper to bypass Enterprise reCAPTCHA
 * using a real Chrome browser with stealth measures to avoid bot detection.
 *
 * Usage: node creci_sp_scraper.js <creci_number> <tipo_creci>
 * Example: node creci_sp_scraper.js 123546 F
 * 
 * Output: JSON on stdout with broker data
 * {
 *   "success": true,
 *   "data": {
 *     "inscricao": "123546",
 *     "nomeCompleto": "RAPHAEL ROBERTO FLORIANI",
 *     "situacao": "Ativo",
 *     "cidade": "",
 *     "estado": "SP",
 *     "dataInscricao": "24/09/2012"
 *   }
 * }
 * 
 * On error:
 * { "success": false, "error": "Error message" }
 */

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

puppeteer.use(StealthPlugin());

const SEARCH_URL = 'https://www.crecisp.gov.br/cidadao/buscaporcorretores';
const CHROME_PATH = process.env.CHROME_PATH || '/usr/bin/google-chrome';

function output(obj) {
    process.stdout.write(JSON.stringify(obj));
}

async function scrape(creciNumber, tipoCreci) {
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
            ],
            timeout: 30000,
        });

        const page = await browser.newPage();

        await page.setUserAgent(
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        );
        await page.setViewport({ width: 1366, height: 768 });

        // Step 1: Load search page
        await page.goto(SEARCH_URL, { waitUntil: 'networkidle2', timeout: 30000 });

        // Step 2: Fill in CRECI number (with delay to simulate typing)
        await page.type('#RegisterNumber', creciNumber, { delay: 80 });

        // Step 3: Wait for Enterprise reCAPTCHA to be fully loaded
        await page.waitForFunction(() => {
            return typeof grecaptcha !== 'undefined' &&
                   grecaptcha.enterprise &&
                   typeof grecaptcha.enterprise.execute === 'function';
        }, { timeout: 15000 });

        // Small delay to let reCAPTCHA finish initialization
        await new Promise(r => setTimeout(r, 2000));

        // Step 4: Execute Enterprise reCAPTCHA and submit via the page's own onSubmit callback
        // This replicates the exact flow the site uses: get token → set hidden field → submit form
        const siteKey = await page.evaluate(() => {
            const btn = document.querySelector('button.g-recaptcha');
            return btn ? btn.getAttribute('data-sitekey') : null;
        });

        if (!siteKey) {
            throw new Error('Could not find reCAPTCHA site key on the page');
        }

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 45000 }),
            page.evaluate((key) => {
                return grecaptcha.enterprise.execute(key, { action: 'submit_broker_search' })
                    .then(token => {
                        document.getElementById('ReCAPTCHAToken').value = token;
                        document.getElementById('IsFinding').value = 'True';
                        document.getElementById('buscaCorretoresForm').submit();
                    });
            }, siteKey),
        ]);

        // Step 6: Check for captcha validation error
        const pageText = await page.evaluate(() => document.body.innerText);
        if (pageText.includes('Validação reCAPTCHA') || pageText.includes('erro na validação do capatcha')) {
            throw new Error('Captcha validation failed on CRECI SP server');
        }

        // Step 7: Find the broker in the results
        const registerNumberQuery = `${creciNumber}-${tipoCreci}`;
        const brokerFound = await page.evaluate((query) => {
            const forms = document.querySelectorAll('form[action*="corretordetalhes"]');
            for (const form of forms) {
                if (form.action.includes(query)) {
                    return true;
                }
            }
            // Check if any form exists (single result)
            return forms.length > 0;
        }, registerNumberQuery);

        if (!brokerFound) {
            // Try extracting from the list even without the detail link
            const brokerFromList = await page.evaluate((query) => {
                const h6s = document.querySelectorAll('.broker-details h6');
                if (h6s.length > 0) {
                    return { name: h6s[0].textContent.trim() };
                }
                return null;
            }, registerNumberQuery);

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

        // Step 8: Click the Detalhes form (POST to detail page)
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
                // Submit the first form if no exact match
                if (forms.length > 0) forms[0].submit();
            }, registerNumberQuery),
        ]);

        // Step 9: Extract detail data
        const detailData = await page.evaluate(() => {
            const data = {};

            // Extract name from h1 subtext or dedicated element
            const heading = document.querySelector('h1');
            if (heading) {
                // The page title is "Detalhes do (a)Corretor(a)"
                // The name is usually in a separate element after the heading
            }

            // Extract all text content and parse it
            const bodyText = document.body.innerText;
            const lines = bodyText.split('\n').map(l => l.trim()).filter(l => l.length > 0);

            // Find name - it appears after "Detalhes do (a)Corretor(a)"
            let nameIndex = lines.findIndex(l => l.includes('Detalhes do'));
            if (nameIndex >= 0 && nameIndex + 1 < lines.length) {
                data.nomeCompleto = lines[nameIndex + 1];
            }

            // Find CRECI
            const creciLine = lines.find(l => l.startsWith('CRECI:'));
            if (creciLine) {
                data.creci = creciLine.replace('CRECI:', '').trim();
            }

            // Find Data de Inscrição
            const dataLine = lines.find(l => l.startsWith('Data de Inscrição:'));
            if (dataLine) {
                data.dataInscricao = dataLine.replace('Data de Inscrição:', '').trim();
            }

            // Find Situação
            const situacaoLine = lines.find(l => l.startsWith('Situação:'));
            if (situacaoLine) {
                data.situacao = situacaoLine.replace('Situação:', '').trim();
            }

            // Find E-Mail
            const emailLine = lines.find(l => l.startsWith('E-Mail Oficial:'));
            if (emailLine) {
                data.email = emailLine.replace('E-Mail Oficial:', '').trim();
            }

            return data;
        });

        if (!detailData.nomeCompleto) {
            throw new Error('Could not extract broker name from detail page');
        }

        // Extract just the number from CRECI field (e.g., "123546-F" -> "123546")
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

// Main
const args = process.argv.slice(2);
if (args.length < 2) {
    output({ success: false, error: 'Usage: node creci_sp_scraper.js <creci_number> <tipo_creci>' });
    process.exit(1);
}

const [creciNumber, tipoCreci] = args;

scrape(creciNumber, tipoCreci)
    .then(result => {
        output(result);
        process.exit(0);
    })
    .catch(err => {
        output({ success: false, error: err.message });
        process.exit(1);
    });
