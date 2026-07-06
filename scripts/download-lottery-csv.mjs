import { chromium } from 'playwright';
import { writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CSV_PATH = join(__dirname, '..', 'storage', 'app', 'jumbo.csv');
const JUMBO_PAGE_URL = 'https://www.mizuhobank.co.jp/retail/takarakuji/tsujyo/jumbo/';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        locale: 'ja-JP',
        acceptDownloads: true,
    });

    const page = await context.newPage();

    console.log('みずほ銀行のジャンボページにアクセス中...');
    await page.goto(JUMBO_PAGE_URL, {
        waitUntil: 'networkidle',
        timeout: 60000,
    });

    // CSVリンクを取得
    const csvHref = await page.evaluate(() => {
        const link = document.querySelector('a[href*="jumbo.csv"]');
        return link ? link.href : null;
    });

    let csvBuffer;

    if (csvHref) {
        console.log(`CSVリンク検出: ${csvHref}`);
        const response = await page.goto(csvHref, { timeout: 30000 });
        if (!response.ok()) {
            throw new Error(`CSV取得失敗: HTTP ${response.status()}`);
        }
        csvBuffer = await response.body();
    } else {
        // フォールバック: タイムスタンプ付きURLで直接リクエスト
        const fallbackUrl = `${JUMBO_PAGE_URL}csv/jumbo.csv?${Date.now()}`;
        console.log(`CSVリンクが見つからないためフォールバック: ${fallbackUrl}`);
        const response = await page.goto(fallbackUrl, { timeout: 30000 });
        if (!response.ok()) {
            throw new Error(`CSV取得失敗: HTTP ${response.status()}`);
        }
        csvBuffer = await response.body();
    }

    writeFileSync(CSV_PATH, csvBuffer);
    console.log(`CSV保存完了: ${CSV_PATH} (${csvBuffer.length} bytes)`);

    await browser.close();
})().catch(err => {
    console.error('エラー:', err.message);
    process.exit(1);
});
