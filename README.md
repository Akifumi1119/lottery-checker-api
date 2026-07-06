# lottery-checker-api

宝くじ（ジャンボ系）の当選番号を自動取得し、JSON形式のREST APIとして提供するサービス。

---

## 概要

| 項目 | 内容 |
|------|------|
| フレームワーク | Laravel 12 (PHP 8.4) |
| データソース | みずほ銀行 宝くじページ（CSV） |
| 更新頻度 | 毎月1日・16日（UTC 02:00 / JST 11:00） |
| デプロイ先 | Render |

---

## システム構成

```
GitHub Actions
  │
  ├─ scripts/download-lottery-csv.mjs   Playwright でCSVをスクレイピング
  │                                      → storage/app/jumbo.csv
  │
  ├─ php artisan lottery:fetch           CSVをパースしてJSONに変換
  │                                      → storage/app/lottery.json
  │
  ├─ git auto-commit                     変更があればlottery.jsonをコミット
  │
  └─ Render deploy hook                  変更があればRenderに再デプロイ通知

Render (PHP 8.4 / Laravel)
  └─ GET /api/lotteries                  lottery.jsonをそのまま返す
```

---

## APIエンドポイント

### GET /api/lotteries

全回分の宝くじ当選番号一覧を返す。

**レスポンス例**

```json
[
  {
    "round": "1107",
    "name": "ドリームジャンボミニ",
    "draw_date": "令和8年6月10日",
    "prizes": [
      {
        "rank": "1等",
        "amount": "5000万円",
        "rule": "58組",
        "number": "112994"
      },
      {
        "rank": "1等の前後賞",
        "amount": "2500万円",
        "rule": "1等の前後の番号",
        "number": ""
      }
    ]
  }
]
```

**レスポンスフィールド**

| フィールド | 型 | 説明 |
|-----------|-----|------|
| `round` | string | 回号（数字のみ） |
| `name` | string | 宝くじ名称 |
| `draw_date` | string | 抽選日（和暦） |
| `prizes[].rank` | string | 等級（例: `1等`、`1等の前後賞`） |
| `prizes[].amount` | string | 賞金額（例: `3億円`） |
| `prizes[].rule` | string | 当選ルール（組・下N桁等） |
| `prizes[].number` | string | 当選番号（前後賞など該当なしの場合は空文字） |

---

## データ更新フロー

### 1. CSVダウンロード（`scripts/download-lottery-csv.mjs`）

- Playwright（Stealth Plugin）でみずほ銀行のジャンボページを開く
- ページのブラウザコンテキストから`jumbo.csv`をfetch（Bot対策回避）
- `storage/app/jumbo.csv` に保存

### 2. JSON生成（`php artisan lottery:fetch`）

- `jumbo.csv`（Shift_JIS）を読み込み、UTF-8に変換
- 「第N回」行を回情報、「N等」行を等級情報として解析
- 全角英数字・全角スペースを正規化
- `storage/app/lottery.json` に出力

### 3. 自動コミット・デプロイ

- `stefanzweifel/git-auto-commit-action` で差分があればコミット
- 変更検出時のみRender Deploy Hookを叩いて再デプロイ

---

## ローカル開発

### 前提条件

- PHP 8.4+
- Composer
- Node.js 20+

### セットアップ

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### CSVダウンロード

```bash
npm install playwright playwright-extra puppeteer-extra-plugin-stealth
npx playwright install chromium --with-deps
node scripts/download-lottery-csv.mjs
```

### JSON生成

```bash
php artisan lottery:fetch
```

### 開発サーバー起動

```bash
php artisan serve
# → http://localhost:8000/api/lotteries
```

---

## Dockerでの起動

```bash
docker build -t lottery-checker-api .
docker run -p 10000:10000 lottery-checker-api
# → http://localhost:10000/api/lotteries
```

---

## CI/CD（GitHub Actions）

ワークフロー: `.github/workflows/fetch-lottery.yml`

**トリガー**
- スケジュール: 毎月1日・16日 UTC 02:00
- 手動: `workflow_dispatch`

**必要なシークレット**

| シークレット名 | 用途 |
|---------------|------|
| `RENDER_DEPLOY_HOOK_URL` | Renderへのデプロイ通知URL |
