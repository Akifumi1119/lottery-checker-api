import { chromium } from 'playwright-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import { writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

chromium.use(StealthPlugin());

const __dirname = dirname(fileURLToPath(import.meta.url));

const TARGETS = [
    {
        pageUrl: 'https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/jumbo/',
        csvUrl: (ts) => `https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/jumbo/csv/jumbo.csv?${ts}`,
        savePath: join(__dirname, '..', 'storage', 'app', 'jumbo.csv'),
        label: 'ジャンボ宝くじ',
    },
    {
        pageUrl: 'https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/zenkoku/',
        csvUrl: (ts) => `https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/zenkoku/csv/zenkoku.csv?${ts}`,
        savePath: join(__dirname, '..', 'storage', 'app', 'zenkoku.csv'),
        label: '全国通常宝くじ',
    },
];

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        locale: 'ja-JP',
    });

    const page = await context.newPage();

    for (const target of TARGETS) {
        console.log(`みずほ銀行の${target.label}ページにアクセス中...`);
        await page.goto(target.pageUrl, {
            waitUntil: 'networkidle',
            timeout: 60000,
        });

        const csvUrl = target.csvUrl(Date.now());
        console.log(`CSVをブラウザコンテキストからダウンロード: ${csvUrl}`);

        const csvBytes = await page.evaluate(async ([url, referer]) => {
            const response = await fetch(url, {
                credentials: 'include',
                headers: { 'Referer': referer },
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const buffer = await response.arrayBuffer();
            return Array.from(new Uint8Array(buffer));
        }, [csvUrl, target.pageUrl]);

        const csvBuffer = Buffer.from(csvBytes);
        writeFileSync(target.savePath, csvBuffer);
        console.log(`CSV保存完了: ${target.savePath} (${csvBuffer.length} bytes)`);
    }

    await browser.close();
})().catch(err => {
    console.error('エラー:', err.message);
    process.exit(1);
});
