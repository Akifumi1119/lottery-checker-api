# lottery-checker-api

宝くじ（ジャンボ系・通常宝くじ各種）の当選番号を自動取得し、JSON形式のREST APIとして提供するサービス。

---

## 概要

| 項目 | 内容 |
|------|------|
| フレームワーク | Laravel 13 (PHP 8.4) |
| データソース | みずほ銀行 宝くじページ（CSV） |
| 更新頻度 | 毎月1日・16日（UTC 02:00 / JST 11:00） |
| デプロイ先 | Render |

---

## システム構成

```
GitHub Actions
  │
  ├─ scripts/download-lottery-csv.mjs   Playwright でCSVをスクレイピング
  │                                      → storage/app/{jumbo,zenkoku,tokyo,kct,kinki,nishinihon,chiiki}.csv
  │
  ├─ php artisan lottery:fetch           CSVをパースしてJSONに変換
  │                                      → storage/app/{lottery,zenkoku,tokyo,kct,kinki,nishinihon,chiiki}.json
  │
  ├─ git auto-commit                     変更があればJSONファイルをコミット
  │
  └─ Render deploy hook                  変更があればRenderに再デプロイ通知

Render (PHP 8.4 / Laravel)
  └─ GET /api/lotteries/*                各JSONをそのまま返す
```

---

## APIエンドポイント

### 宝くじ当選番号

| エンドポイント | 宝くじ種別 |
|--------------|-----------|
| `GET /api/lotteries` | ジャンボ宝くじ |
| `GET /api/lotteries/zenkoku` | 全国通常宝くじ |
| `GET /api/lotteries/tokyo` | 東京都宝くじ |
| `GET /api/lotteries/kct` | 関東・中部・東北自治宝くじ |
| `GET /api/lotteries/kinki` | 近畿宝くじ |
| `GET /api/lotteries/nishinihon` | 西日本宝くじ |
| `GET /api/lotteries/chiiki` | 地域医療等振興自治宝くじ |

各エンドポイントとも全回分の当選番号一覧を返す。

**レスポンス例**

```json
[
  {
    "round": "1107",
    "name": "全国自治宝くじ（ドリームジャンボミニ）",
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
| `name` | string | 宝くじ名称（`系列名（固有名）` の形式） |
| `draw_date` | string | 抽選日（和暦） |
| `prizes[].rank` | string | 等級（例: `1等`、`1等の前後賞`） |
| `prizes[].amount` | string | 賞金額（例: `3億円`） |
| `prizes[].rule` | string | 当選ルール（組・下N桁等） |
| `prizes[].number` | string | 当選番号（前後賞など該当なしの場合は空文字） |

### アクセス統計

```
GET /api/stats
```

エンドポイントごとのアクセス数を返す。

**レスポンス例**

```json
{
  "lotteries": 120,
  "lotteries/zenkoku": 45,
  "lotteries/tokyo": 12,
  "lotteries/kct": 8,
  "lotteries/kinki": 6,
  "lotteries/nishinihon": 5,
  "lotteries/chiiki": 3,
  "total": 199
}
```

---

## データ更新フロー

### 1. CSVダウンロード（`scripts/download-lottery-csv.mjs`）

- Playwright（Stealth Plugin）でみずほ銀行の各宝くじページを開く
- ページのブラウザコンテキストからCSVをfetch（Bot対策回避）
- 以下7ファイルを `storage/app/` に保存

| ファイル | 宝くじ種別 |
|---------|-----------|
| `jumbo.csv` | ジャンボ宝くじ |
| `zenkoku.csv` | 全国通常宝くじ |
| `tokyo.csv` | 東京都宝くじ |
| `kct.csv` | 関東・中部・東北自治宝くじ |
| `kinki.csv` | 近畿宝くじ |
| `nishinihon.csv` | 西日本宝くじ |
| `chiiki.csv` | 地域医療等振興自治宝くじ |

### 2. JSON生成（`php artisan lottery:fetch`）

- 各CSV（Shift_JIS）を読み込み、UTF-8に変換
- 「第N回」行を回情報、「N等」「N賞」行を等級情報として解析
- 名称は `系列名（固有名）` の形式に整形
- 全角英数字・全角スペースを正規化
- 対応するJSONファイルを `storage/app/` に出力

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

または `composer setup` で一括実行。

### CSVダウンロード

```bash
npm install
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
