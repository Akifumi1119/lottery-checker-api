import { chromium } from 'playwright-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import { writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

chromium.use(StealthPlugin());

const __dirname = dirname(fileURLToPath(import.meta.url));
const CSV_PATH = join(__dirname, '..', 'storage', 'app', 'jumbo.csv');
const JUMBO_PAGE_URL = 'https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/jumbo/';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        locale: 'ja-JP',
    });

    const page = await context.newPage();

    console.log('みずほ銀行のジャンボページにアクセス中...');
    await page.goto(JUMBO_PAGE_URL, {
        waitUntil: 'networkidle',
        timeout: 60000,
    });

    const csvUrl = `${JUMBO_PAGE_URL}csv/jumbo.csv?${Date.now()}`;
    console.log(`CSVをブラウザコンテキストからダウンロード: ${csvUrl}`);

    const csvBytes = await page.evaluate(async (url) => {
        const response = await fetch(url, {
            credentials: 'include',
            headers: {
                'Referer': 'https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/jumbo/',
            },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const buffer = await response.arrayBuffer();
        return Array.from(new Uint8Array(buffer));
    }, csvUrl);

    const csvBuffer = Buffer.from(csvBytes);
    writeFileSync(CSV_PATH, csvBuffer);
    console.log(`CSV保存完了: ${CSV_PATH} (${csvBuffer.length} bytes)`);

    await browser.close();
})().catch(err => {
    console.error('エラー:', err.message);
    process.exit(1);
});
